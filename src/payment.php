<?php

namespace Pooldevmtdpce\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Exception;
use Pooldevmtdpce\Payment\models\OMResponse;

class Paiement 
{
    public function payOrange($montant, $numberUser, $codeOtp): OMResponse
    {
        $omResponse = new OMResponse(false, "Paiement non valide", "");
        if ($numberUser== null || $codeOtp == null || $codeOtp == '' || $montant== null) {
            return $omResponse;
        }
        $numberUser = str_replace(' ', '', $numberUser);
        $codeOtp = str_replace(' ', '', $codeOtp);
        $username =env("OM_USERNAME");//Nom d’utilisateur du partenaire pour l’API fourni par Orange
        $password =env("OM_PASSWORD"); //Mot de passe du partenaire pour l’API fourni par Orange
        $referencenumber =env("OM_REFERNCE_NUMBER"); // Information supplémentaire que le partenaire/Accepteur pourra envoyer.
        $exttxtid =env("OM_REFERENCE_TRANSACTION");//Reference de transaction du partenaire/Accepteur
        $numberReceiver =env("OM_NUMBER_RECEIVER");//Numero marchant
        $url = env("OM_URL");

        $client = new \GuzzleHttp\Client(['verify' => false]); //Verify = false : désactiver la vérification du certificat 

        //Construction du paramètre xml à envoyer
        $xml = "<?xml version='1.0' encoding='UTF-8'?>
        <COMMAND>
            <TYPE>OMPREQ</TYPE>
            <customer_msisdn>{$numberUser}</customer_msisdn>
            <merchant_msisdn>{$numberReceiver}</merchant_msisdn>
            <api_username>{$username}</api_username>
            <api_password>{$password}</api_password>
            <amount>{$montant}</amount> 
            <PROVIDER>101</PROVIDER>
            <PROVIDER2>101</PROVIDER2>
            <PAYID>12</PAYID> 
            <PAYID2>12</PAYID2>
            <otp>{$codeOtp}</otp>
            <reference_number>{$referencenumber}</reference_number>
            <ext_txn_id>{$exttxtid}</ext_txn_id>
        </COMMAND>";

        $method = $client->post($url, ['body' => $xml]);

        try {
            $statusCode = $method->getStatusCode();
            /**
             * Orange verifie les informations transmises dans le xml
             * Transaction validée : code statut = 200
             * Transaction invalidée : code statut est différent de 200 (voir liste des codes erreurs)
             */
            //Paiement non validé
            if ($statusCode != '200') {
                $omResponse = new OMResponse(false, "", "");
                return $omResponse;
            }
            //Paiement validé : on continue l'opération 
            $responseBody = $method->getBody();

            $responseXML  = "<?xml version='1.0' encoding='UTF-8'?>";
            $responseXML .= "    <omResponse>";
            $responseXML .=         (string)$responseBody;
            $responseXML .= "    </omResponse>";
            //Traitement du paiement validé
            return $this->paiementOK( $responseXML, $numberUser, $codeOtp, $montant);
        } catch (Exception $ex) {
            return $omResponse;
        } finally {
            // Release the connection. 
            $method->getBody()->close();
        }

        return $omResponse;
    }

    private function paiementOK( string $xmlString, string $numeroClient, string $otp, string $fraisdossier)
    {
        $statusPaiement = false;
        $response_status = '';
        $trans_id = '';
        $response_text = '';
        $omResponse = null;
        try {
            //Traitement de la reponse
            $document = new \DOMDocument();
            $document->loadXML($xmlString);
            $noeudStatus = $document->getElementsByTagName('status');//Recuperation du Statut
            $noeudMessage = $document->getElementsByTagName('message');//Recuperation du message
            $noeudTransID = $document->getElementsByTagName('transID');//Recuperation de l'ID de la Transaction
            $erreurExpire = false;
            //Verifie que le noeud n'est pas vide
            if ($noeudStatus->length > 0) {
                $status = $noeudStatus->item(0);
                $response_status = $status->textContent;
                    //On s'assure que le paiement a été validé
                if ('200' == trim($response_status)) {
                    $statusPaiement = true;
                    if ($noeudTransID->length > 0) {
                        $noeudTransID = $noeudTransID->item(0);
                        $trans_id = $noeudTransID->textContent;
                    }
                    if ($noeudMessage->length > 0) {
                        $message = $noeudMessage->item(0);
                        $response_text = $message->textContent;
                    }
                    $omResponse = new OMResponse(true, $response_text, $trans_id);
                } else {
                        //Paiement non validé
                    if ('200' != trim($status->textContent)) {
                        $erreurExpire = true;
                        $omResponse = new OMResponse(false, "Paiement non valide", "");
                    }
                }
            } else {
                $omResponse = new OMResponse(false, "Paiement non valide", "");
            }
        } catch (Exception $e) {
            $omResponse = new OMResponse(false, "Paiement non valide", "");
        }
        return $omResponse;
    }    
}



