# LV Enterprise Register API access
Based on https://viss.gov.lv/lv/Informacijai/Dokumentacija/Koplietosanas_komponentes/API_Parvaldnieks

## Setup steps after receiving certs from VRAA
1. Convert .cer to .pfx
```
pkcs12 -export -in c:\temp\xxxxx.cer -inkey c:\temp\xxxxx.key -out c:\temp\xxxxx.pfx
```
2. Convert .pfx to .pem
```
openssl pkcs12 -in c:\ABCTestCert.friendlyname.pfx -out c:\Temp\ABCTestCert.friendlyname.pem
```
3. Fill values: (Client&secret are generated in https://api.viss.gov.lv/store/site/pages/applications.jag):
```
TOKEN_CLIENT_ID, TOKEN_CLIENT_SECRET, CERTIFICATE (.pem), CERTIFICATE_PASSWORD
```
4. Documentation for Enterprise register REST API:
https://api.viss.gov.lv/store/apis/info?name=UR-API-LegalEntity&version=v1.0&provider=UR_MKANEPE#tab1
or included document UR_LE_servisa_apraksts_V4.docx
