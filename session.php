<?php
// Using Cookie for managing session data with encryption.

class MySessionHandler implements SessionHandlerInterface
{
    function __construct()
    {
        // Store the key and IV somewhere safe
        //$key = openssl_random_pseudo_bytes(32); // 256-bit key
        //$iv = openssl_random_pseudo_bytes(16); // 128-bit IV
        
        // Store the base64 key and IV somewhere safe
        //$key_base64 = base64_encode($key);
        //$iv_base64 = base64_encode($vi);

        // Use the store base64 key and IV below
        $key_base64 = 's8Livn/jULM6HDdPY76E3aXtfELdleTaqOC8HgTfW7M=';
        $iv_base64 = 'nswqKP23TT+deVNuaV5nXQ==';
        $this->key = base64_decode($key_base64);
        $this->iv = base64_decode($iv_base64);
    }

    // Encryption
    function encryptSess($plaintext)
    {
        return openssl_encrypt($plaintext, 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $this->iv);	
    }

    // Decryption
    function decryptSess($ciphertext)
    {
        return openssl_decrypt($ciphertext, 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $this->iv);
    }

    public function open($savePath, $sessionName): bool
    {
        ob_start(); // Turn on output buffering
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read($id)
    {
        if (isset($_COOKIE[session_name()])) {
            return (string)$this->decryptSess(base64_decode($_COOKIE[session_name()]));
        } else {
            return '';
        }
    }

    public function write($id, $data): bool
    {
        $op = ob_get_clean();
        $encryptedData = base64_encode($this->encryptSess($data));
        setcookie(session_name(), $encryptedData, time() + (ini_get("session.gc_maxlifetime")), '/');
        echo $op;

        return true;
    }

    public function destroy($id): bool
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function gc($maxlifetime)
    {
        return true;
    }
}

$handler = new MySessionHandler();
session_set_save_handler($handler, true);
session_start();
var_dump($_SESSION);
$_SESSION['id'] = 10000;

echo '<br/>Hello World';
?>
Class for using Cookie for managing session data with encryption.

For heavily loaded web application SESSION we have seen different modes of saving session data
for example files and databases. To manage heavily loaded websites SESSION having
millions of visitors; it will be difficult to maintain files or a database table as a mode
for maintaining SESSION. This class provides solution to this problem by using COOKIE
as a storage media in a safer way with encryption.
