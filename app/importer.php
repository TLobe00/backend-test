<?php
require '../vendor/autoload.php';

use App\SQLiteConnection;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;

$filesystem = new Filesystem();
$fileLoader = new FileLoader($filesystem, '');
$translator = new Translator($fileLoader, 'en_US');
$factory = new Factory($translator);

$pdo = (new SQLiteConnection())->connect();
if ($pdo != null) {

    $pdo->query("PRAGMA foreign_keys=ON");
    if (isset($argc)) {
        if ($argc != 3) {
            echo "You must pass both filenames into the importer\n";
            exit();
        }

        if (!file_exists($argv[1]) || !file_exists($argv[2])) {
            echo "Both files MUST exist in the directory\n";
            exit();
        }

        importCustomers($argv[1]);
        print "---------------------------\n";
        importLocation($argv[2]);
    }
    else {
        echo "argc and argv disabled\n";
        exit();
    }
} else {
    echo "Whoops, could not connect to the SQLite database!\n";
    exit();
}

function importCustomers($file)
{
    global $filesystem, $fileLoader, $translator, $factory, $pdo;

    ini_set('auto_detect_line_endings',TRUE);
    $handle = fopen($file,'r');
    fgetcsv($handle);
    while ( ($data = fgetcsv($handle) ) !== FALSE ) {

        try {

            $messages = [
                'numeric' => 'ERR:CUSTNO[:attribute] - Customer Number must be numeric',
                'required_unless' => 'ERR:NAME - You must have first or last name filled in.',
                'email' => 'ERR:EMAIL - Email must be in the correct format.',
                'regex' => 'ERR:PHONE [:attribute] - Invalid phone number',
            ];
            
            $dataToValidate = $data;
            $rules = [
                '0' => 'numeric',
                '1' => 'string|required_unless:2,null',
                '2' => 'string|required_unless:1,null',
                '3' => 'email:spoof',
                '4' => 'regex:/^\+?\d?\s?\(?(\d{3})\)?[-\. ]?(\d{3})[-\. ]?(\d{4})/',
                '5' => 'regex:/^\+?\d?\s?\(?(\d{3})\)?[-\. ]?(\d{3})[-\. ]?(\d{4})/',
                '7' => 'numeric',
            ];
            
            $validator = $factory->make($dataToValidate, $rules, $messages);
            
            if($validator->fails()){
                $errors = $validator->errors();
                foreach($errors->all() as $message){
                    if ( preg_match('/(ERR:CUSTNO\[0\])/', $message) ) {
                        $data[0] = null;

                        $stmt = $pdo->query('SELECT CustomerNumber '
                        . 'FROM customer '
                        . 'WHERE 1=1 '
                        . 'ORDER BY CustomerNumber DESC '
                        . 'LIMIT 1');
        
                        if (!$stmt) {
                            $data[0] = 0;
                        } else {
                            $lastCustomerNo = $stmt->fetchObject();
                    
                            $data[0] = $lastCustomerNo->CustomerNumber+1;
                        }

                    }                
                    if ( preg_match('/(ERR:EMAIL)/', $message) ) {
                        $data[3] = null;
                    }
                    if ( preg_match('/(ERR:CUSTNO\[7\])/', $message) ) {
                        $data[7] = 0;
                    }

                    if ( preg_match('/(ERR:PHONE)/', $message) ) {

                        $tmpV = substr($message,11,1);
                        $data[$tmpV] = null;
                    }
                }
            }

            foreach ( $data as $key => $unescaped ) {
                $escaped = SQLite3::escapeString($unescaped);
                $data[$key] = $escaped;
            }

            for ($i = 4; $i<=5; $i++) {
                if(preg_match(
                    '/^\+?\d?\s?\(?(\d{3})\)?[-\. ]?(\d{3})[-\. ]?(\d{4})$/', 
                $data[$i], $value)) {
                  
                    $format = '(' . $value[1] . ') ' . 
                        $value[2] . '-' . $value[3];
                }
                else {
                    $data[$i] = null;
                }
                $data[$i] = $format;
            }

            $stmt2 = $pdo->query("INSERT OR IGNORE INTO customer(CustomerNumber) VALUES(".$data[0].")");
            $stmt3 = $pdo->query("UPDATE customer SET FirstName='".$data[1]."', LastName='".$data[2]."', EmailAddress='".$data[3]."', MobilePhone='".$data[4]."', HomePhone='".$data[5]."', Birthday='".$data[6]."', Status='".$data[7]."' WHERE CustomerNumber=".$data[0]);
            print "Upserted: CustomerNumber: " . $data[0] . " - " . $data[1] . " " . $data[2] . "\n";
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }

    }
    ini_set('auto_detect_line_endings',FALSE);
}

function importLocation($file)
{
    global $filesystem, $fileLoader, $translator, $factory, $pdo;

    ini_set('auto_detect_line_endings',TRUE);
    $handle = fopen($file,'r');
    fgetcsv($handle);
    while ( ($data = fgetcsv($handle) ) !== FALSE ) {

        try {

            $messages = [
                'numeric' => 'ERR:IDNUM[:attribute] - ID Number must be numeric',
                'required' => 'ERR:REQ[:attribute] - Field is required.',
                'string' => 'ERR:FIELD[:attribute] - Field not correct.'
            ];
            
            $dataToValidate = $data;
            $rules = [
                '0' => 'numeric|required',
                '1' => 'numeric',
                '2' => 'string|required',
                '3' => 'string',
                '4' => 'string',
                '5' => 'string',
                '6' => 'string',
            ];
            
            $validator = $factory->make($dataToValidate, $rules, $messages);
            
            if($validator->fails()){
                $errors = $validator->errors();

                foreach($errors->all() as $message){
                    if ( preg_match('/(ERR:IDNUM\[1\])/', $message) ) {
                        $data[1] = null;

                        $stmt = $pdo->query('SELECT LocationNumber '
                        . 'FROM location '
                        . 'WHERE 1=1 '
                        . 'ORDER BY LocationNumber DESC '
                        . 'LIMIT 1');
        
                        if (!$stmt) {
                            $data[1] = 0;
                        } else {
                            $lastLocationNo = $stmt->fetchObject();
                    
                            $data[1] = $lastLocationNo->LocationNumber+1;
                        }

                    }
                    
                    if ( preg_match('/(ERR:REQ)/', $message) ) {
                        continue 2;
                    }

                    $stmt_custchk = $pdo->query('SELECT CustomerNumber '
                    . 'FROM customer '
                    . 'WHERE CustomerNumber = '.$data[0]);

                    if ( !$stmt_custchk ) {
                        continue 2;
                    } else {
                        $customerNumber = $stmt_custchk->fetchObject();
                        if ( !isset($customerNumber->CustomerNumber) ) {
                            continue 2;
                        }
                    }

                    if ( preg_match('/(ERR:EMAIL)/', $message) ) {
                        $data[3] = null;
                    }
                    if ( preg_match('/(ERR:CUSTNO\[7\])/', $message) ) {
                        $data[7] = 0;
                    }

                    if ( preg_match('/(ERR:PHONE)/', $message) ) {
                        $tmpV = substr($message,11,1);
                        $data[$tmpV] = null;
                    }
                }
            }

            $stmt2 = $pdo->query("INSERT OR IGNORE INTO location(LocationNumber) VALUES(".$data[1].")");
            $stmt3 = $pdo->query("UPDATE location SET CustomerNumber='".$data[0]."', StreetAddress='".$data[2]."', CityName='".$data[3]."', StateCode='".$data[4]."', PostalCode='".$data[5]."', Notes='".$data[6]."' WHERE LocationNumber=".$data[1]);

            print "Upserted: LocationNumber: " . $data[1] . " - " . $data[2] . "\n";
            print " - CustomerNumber: " . $data[0] . "\n";
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }

    }
    ini_set('auto_detect_line_endings',FALSE);
}