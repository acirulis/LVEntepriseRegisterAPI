<?php
require_once("JWT.php");

use Firebase\JWT\JWT;

class LVURAPI
{

    const TOKEN_URL = "https://apigw.viss.gov.lv/token"; //VISS Production env

    const TOKEN_CLIENT_ID = "***"; //Klientu lietojuma Consumer_key bez urn:oauth2: prefiksa no API Store.
    const TOKEN_CLIENT_SECRET = "***"; /*  Consumer_secret no API Store. */

    const CERTIFICATE = "UR_MKANEPE.pem"; //PFAS AUTH lietotāja autentifikācijas sertifikāts, sertifikāts ir reģistrēts PFAS kā autentifikācijas līdzeklis
    const CERTIFICATE_PASSWORD = "***"; // sertifikāta parole

    const CERTIFICATE_TOKEN_URL = "https://lvp.viss.gov.lv/STS/VISS.Pfas.STS/oauth2/token"; // VISS testa vide. !!!Jūtīgs pret burtu reģistru.


    //Class variables
    public $token;


    function __construct($existing_token = null)
    {
        if (isset($existing_token))
            $this->token = $existing_token;
        else
            $this->token = $this->getAccessTokenWithSertificate();
    }

    function getAccessTokenWithSertificate()
    {
        $privateKey = openssl_get_privatekey(file_get_contents(self::CERTIFICATE), self::CERTIFICATE_PASSWORD);

        $payload = array(
            "sub" => self::TOKEN_CLIENT_ID,
            "jti" => uniqid(),
            "iss" => self::TOKEN_CLIENT_ID,
            "aud" => self::CERTIFICATE_TOKEN_URL, //self::TOKEN_URL, // Use the TOKEN_URL if you get a token without using wso2
            "nbf" => time(),
            "exp" => time() + 600
        );

        // Get public certificate
        $cert = openssl_x509_read(file_get_contents(self::CERTIFICATE));
        openssl_x509_export($cert, $str_cert);
        $pub_cert = str_replace([
            '-----BEGIN CERTIFICATE-----',
            '-----END CERTIFICATE-----',
            "\r\n",
            "\n",
        ], [
            '',
            '',
            "\n",
            ''
        ], $str_cert);

        //get thumbprint
        $fingerprint = openssl_x509_fingerprint($cert); // sha1 hash

        $header = array(
            "x5c" => $pub_cert,
            "x5t" => JWT::urlsafeB64Encode($fingerprint)
        );

        $jwt = JWT::encode($payload, $privateKey, "RS256", $fingerprint, $header);

        //Configuring URL & SSL
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, self::TOKEN_URL);

        //Turn off all TLS security issues
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);

        //Return result in string
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        //Configuring headers
        $headers = array(
            "cache-control: no-cache",
            "Accept: application/json"
        );

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        //Configuring post request
        curl_setopt($curl, CURLOPT_POST, True);
        curl_setopt($curl, CURLOPT_POSTFIELDS, array(
            "grant_type" => "client_credentials",
            "client_assertion_type" => "urn:ietf:params:oauth:client-assertion-type:jwt-bearer",
            "client_secret" => self::TOKEN_CLIENT_SECRET,
            "client_id" => self::TOKEN_CLIENT_ID,
            "client_assertion" => $jwt
//            "scope" => self::TOKEN_SCOPE
        ));

        //Getting result
        $result = curl_exec($curl);
        $json = json_decode($result, true);
        curl_close($curl);

        //Saving & reporting
        echo "Getted auth token " . $json["access_token"] . "<br>";

        //return substr($json["access_token"], 9);
        return $json["access_token"];
    }

    function getLegalEntityBasicData($reg_nr)
    {
        $URL = sprintf('https://apigw.viss.gov.lv/legalentity/v1.0/legal-entity/%s', $reg_nr);
        return $this->make_get_call($URL);
    }

    function getNaturalPerson($social_id)
    {
        $URL = sprintf('https://apigw.viss.gov.lv/naturalperson/v1.0/natural-person/%s', $social_id);
        return $this->make_get_call($URL);
    }

    function getForeignEntity($id)
    {
        $URL = sprintf('https://apigw.viss.gov.lv/foreignentity/v1.0/foreign-entity/%s', $id);
        return $this->make_get_call($URL);
    }

    private function make_get_call($URL)
    {
        //Configuring URL & SSL
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $URL);

        //Turn off all TLS security issues
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);

        //Return result in string
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        //Configuring headers
        $headers = array(
            "cache-control: no-cache",
            "Accept: application/json",
            "Authorization: Bearer " . $this->token,
        );

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        //Getting result
        $result = curl_exec($curl);
//        var_dump(curl_error($curl));

        $json = json_decode($result, true);
        curl_close($curl);
        return $json;
    }
}

$token = '***';
$EntepriseRegister = new LVURAPI();
//$EntepriseRegister = new LVURAPI($token);

//$company = $EntepriseRegister->getLegalEntityBasicData('40003000642');
//var_dump($company);
//var_dump($company['officers']);

$person = $EntepriseRegister->getNaturalPerson('800047-00008');
var_dump($person);

//$foreign = $EntepriseRegister->getForeignEntity('800047-00008');
//var_dump($foreign);