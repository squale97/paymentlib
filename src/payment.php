<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
//use Zend\XmlRpc\Client;

class Paiement 
{
    


       

    public function payOrange($montant, $numberUser, $numberReceiver, $username, $password)
    {
        $url = 'http://example.com/xml-rpc-endpoint'; // Remplacez par l'URL de votre endpoint

        $xmlData = [`<?xml version="1.0" encoding="UTF-8"?>
                    <COMMAND>
                        <TYPE>OMPREQ</TYPE>
                        <customer_msisdn>$numberUser</customer_msisdn>
                        <merchant_msisdn>$numberReceiver</merchant_msisdn>
                        <api_username>$username</api_username>
                        <api_password>$password</api_password>
                        <amount>$montant</amount>
                        <PROVIDER>101</PROVIDER>
                        <PROVIDER2>101</PROVIDER2>
                        <PAYID>12</PAYID>
                        <PAYID2>12</PAYID2>
                        <otp>541847</otp>
                        <reference_number>789233</reference_number>
                        <ext_txn_id>201500068544</ext_txn_id>
                    </COMMAND>`];

        $response = Http::withHeaders([
            'Content-Type' => 'text/xml', // Spécifiez le type de contenu comme XML
        ])->post($url, $xmlData);

        // Vérifiez si la requête a réussi (code de statut 2xx)
        if ($response->successful()) {
            $result = $response->body(); // Contient la réponse du serveur
            // Traitez la réponse XML ici
            return $result;
        } 
      else {
        // En cas d'erreur, affichez le code de statut et le message
        return "Erreur : " . $response->status() . " - " . $response->body();
      }
    }
    
}
