 <?php

 require_once('AES128_php.php'); 
 $AESobj=new AESEncDec();

$data='Shipper|Mayuresh Enclave, Sector 20, Plat A-211, Nerul,Navi-Mumbai,403706|Mumbai|Maharastra|India|403706|+91|222|30988373|9812345678|N|4f9cf81e205f1f1f7de3c69ea81b1c7547ce90240782c0f5bc4a7244c42f9bc8c4c776c152c9d0a66309a51ae273cf5c0cb86a50d938e40da6cf453a4d0da525';

$key='fBc5628ybRQf88f/aqDUOQ==';


$cipherText = $AESobj->encrypt($data,$key);  
 echo "ciphettext in SBIePAY enc dec is as below IS-----------".$cipherText;

//$cipherText contains encrypted data
//Calling decrypt method
$plaintext = $AESobj->decrypt($cipherText,$key);

//Display decrypted text
echo "plaintext  in SBIePAY enc dec is as below  IS-----------".$plaintext;

?>