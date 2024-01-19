<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Exception;


//use Zend\XmlRpc\Client;



class Paiement 
{
    


       

    public function payOrange($montant, $numberUser, $codeOtp)
    {
        if ($numberUser== null || $codeOtp == null || $codeOtp == '' || $montant== null) {
            return false;
        }
        $numberUser = str_replace(' ', '', $numberUser);
        $codeOtp = str_replace(' ', '', $codeOtp);
        $username =env("OM_USERNAME");//Nom d’utilisateur du partenaire pour l’API fourni par Orange
        $password =env("OM_PASSWORD"); //Mot de passe du partenaire pour l’API fourni par Orange
        $referencenumber =env("OM_REFERNCE_NUMBER"); // Information supplémentaire que le partenaire/Accepteur pourra envoyer.
        $exttxtid =env("OM_REFERENCE_TRANSACTION");//Reference de transaction du partenaire/Accepteur
        $numberReceiver =env("OM_NUMBER_RECEIVER");//Numero marchant
        

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

        $method = $client->post('https://testom.orange.bf:9008/payment', ['body' => $xml]);

        try {
            $statusCode = $method->getStatusCode();
            /**
             * Orange verifie les informations transmises dans le xml
             * Transaction validée : code statut = 200
             * Transaction invalidée : code statut est différent de 200 (voir liste des codes erreurs)
             */
            //Paiement non validé
            if ($statusCode != '200') {
                return false;
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
            Log::error($ex->getMessage());
            return false;
        } finally {
            // Release the connection. 
            $method->getBody()->close();
        }

        return false;
    }
    
    



    private function paiementOK( string $xmlString, string $numeroClient, string $otp, string $fraisdossier)
    {
        $statusPaiement = false;
        $response_status = '';
        $trans_id = '';

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
                    //Historiser les paiements effectués
                   /* $this->historiquePaiementRepository->create([
                        'code_otp' => $otp,
                        'transaction_id' => $trans_id,
                        'message' => $response_text,
                        'status' => $response_status,
                        'montant' => $fraisdossier,
                        'numero_client' => $numeroClient,
                        'dossier_id' => $dossierId,
                        'moyen_paiement'  => 'Orange Money',
                    ]);*/
                } else {
                        //Paiement non validé
                    if ('200' != trim($status->textContent)) {
                        $erreurExpire = true;
                        Log::error(trim($status->textContent));
                        return $statusPaiement;
                    }
                }
            } else {
                return $statusPaiement;
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $statusPaiement;
        }

        return $statusPaiement;
    }



  /*  public function checkTransaction(string $dossier, string $numeroClient, string $codeOtp, string $numeroMarchand, string $fraisDossier)
    {
        if ($dossier == null || $numeroClient == null || $codeOtp == null || $codeOtp == '' || $numeroMarchand == null || $fraisDossier == null) {
            return false;
        }
        $numeroClient = str_replace(' ', '', $numeroClient);
        $codeOtp = str_replace(' ', '', $codeOtp);
        $username = "username";//Nom d’utilisateur du partenaire pour l’API fourni par Orange
        $password = "123456"; //Mot de passe du partenaire pour l’API fourni par Orange
        $referencenumber = "nom service par exemple ou autre"; // Information supplémentaire que le partenaire/Accepteur pourra envoyer.
        $exttxtid = "numero dossier par exemple";//Reference de transaction du partenaire/Accepteur

        $client = new \GuzzleHttp\Client(['verify' => false]); //Verify = false : désactiver la vérification du certificat 

        //Construction du paramètre xml à envoyer
        $xml = "<?xml version='1.0' encoding='UTF-8'?>
        <COMMAND>
            <TYPE>OMPREQ</TYPE>
            <customer_msisdn>{$numeroClient}</customer_msisdn>
            <merchant_msisdn>{$numeroMarchand}</merchant_msisdn>
            <api_username>{$username}</api_username>
            <api_password>{$password}</api_password>
            <amount>{$fraisDossier}</amount> 
            <PROVIDER>101</PROVIDER>
            <PROVIDER2>101</PROVIDER2>
            <PAYID>12</PAYID> 
            <PAYID2>12</PAYID2>
            <otp>{$codeOtp}</otp>
            <reference_number>{$referencenumber}</reference_number>
            <ext_txn_id>{$exttxtid}</ext_txn_id>
        </COMMAND>";

        $method = $client->post('https://testom.orange.bf:9008/payment', ['body' => $xml]);

        try {
            $statusCode = $method->getStatusCode();
            /**
             * Orange verifie les informations transmises dans le xml
             * Transaction validée : code statut = 200
             * Transaction invalidée : code statut est différent de 200 (voir liste des codes erreurs)
             */
            //Paiement non validé
           /* if ($statusCode != '200') {
                return false;
            }
            //Paiement validé : on continue l'opération 
            $responseBody = $method->getBody();

            $responseXML  = "<?xml version='1.0' encoding='UTF-8'?>";
            $responseXML .= "    <omResponse>";
            $responseXML .=         (string)$responseBody;
            $responseXML .= "    </omResponse>";
            //Traitement du paiement validé
            return $this->paiementOK( $responseXML, $numeroClient, $codeOtp, $fraisDossier);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return false;
        } finally {
            // Release the connection. 
            $method->getBody()->close();
        }

        return false;*/
    
    
}