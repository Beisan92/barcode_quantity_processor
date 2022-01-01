<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\BarcodeQuantity;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Validator;
use ValueError;
use Throwable;

class UploadFileController extends Controller {
   
    public $errorMsg;

    public function upload(Request $request) {
       $validator = Validator::make($request->all(),[ 
            'file' => 'required|mimes:csv,xls,xlsx|max:50000',
        ]);   
 
        if ($validator->fails()) {          
            return response()->json(['error'=>$validator->errors()], 400);                        
        }

        $success = true;

        if ($file = $request->file('file')) {
            $path = $file->store('files');
            $name = $file->getClientOriginalName();
            
            try {
                $dataArray = self::handleFile($path, $name);

                foreach ($dataArray as $key => $value) {
                    $record = BarcodeQuantity::firstWhere('barcode', $key);
                    if ($record != null) {
                        $record->quantity= (int)$value + (int)$record->quantity;
                        $record->save();
                    } else {
                        $save = new BarcodeQuantity();
                        $save->barcode = $key;
                        $save->quantity= $value;
                        $save->save();
                    }
                } 
            } catch (ValidationException $e) {
                $success = false;
                $this->errorMsg = "Unexpected Error occurred, please try again later";
            } catch (ValueError $e) {
                $success = false;
                $this->errorMsg = "Unexpected Error occurred, please try a different file or try again later";
            } catch (Throwable $e) {
                $success = false;
                $this->errorMsg = "Unexpected Error occurred, please try again later";
            } catch (Exception $e) {
                $success = false;
                $this->errorMsg = "Unexpected Error occurred, please try again later";
            }
              
            return response()->json([
                "success" => $success,
                "message" => $this->errorMsg
            ]);
  
        } else {
            return $request->file('file');
        }
    }

    private function handleFile($path, $name) {
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $dataArray = array();

        if ('csv' == $ext) {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        }
        $storagePath  = "/storage/app/public/";//Storage::disk('public')->getDriver()->getAdapter()->getPathPrefix();
        $filePath = $storagePath .  $path;
        echo $filePath . "xxxxxxxx \n" . $storagePath . "\n";
        $spreadsheet = $reader->load($filePath);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        
        if (!empty($sheetData)) {
            for ($i=0; $i<count($sheetData); $i++) {
                if (count($sheetData[$i]) > 1) {
                    if (!empty($sheetData[$i][0]) && is_numeric($sheetData[$i][1])) {
                        if (array_key_exists($sheetData[$i][0], $dataArray)) {
                            $dataArray[$sheetData[$i][0]] = (int)$dataArray[$sheetData[$i][0]] + (int)$sheetData[$i][1];
                        } else {
                            $dataArray[$sheetData[$i][0]] = (int)$sheetData[$i][1];
                        }
                    } else {
                        $this->errorMsg = "Please note that some of the records have been ignored due to an empty barcode or invalid quantity number.";
                    }
                } else {
                    $this->errorMsg = "Please note that some of the records have been ignored due to invalid file format (Barcode, Quantity)";
                }
            }
        } else { 
            throw ValidationException::withMessages([
                $filePath => ["The fils is invalid format or doesn't exist!"],
            ]);
        }
        return $dataArray;
    }
}
