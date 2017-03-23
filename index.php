<?php
/**

    HOW IT WORKS
    Step 1: Connect ResponseTap and get all Call IDs for today
    Step 2: Connect to Google Sheets and get all Call IDs
    Step 3: Compare both returned objects
    Step 4: Connect ResponseTap and get Call Details for IDs not in Google Sheets
    Step 5: Populate Call Details into Google Sheets

**/

//$yesterday = date('Y-m-d',strtotime("-1 days"));

$date = date("Y-m-d");


//Connect ResponseTap to get list of call IDs
$call_id_url = 'https://dashboard.responsetap.com/api/1/accounts/RESPONSETAP_ACCOUNT_NUMBER/cdrids?fromDate='.$date.'&toDate='.$date;
$call_id_list_response = curl_connection($call_id_url);
$call_id_list = json_decode($call_id_list_response, true);
$call_id_list = $res["cdrUniqueIds"];

foreach ($call_id_list as $key => $callID) {

    /*
        Read Google Sheets URL

        STEPS TO GET $spreadsheed_url
        - Go to your Google Sheets
        - Click on File>Publish to Web

    */
    $spreadsheet_url="YOUR_GOOGLE_SHEET_CODE";

    //Empty array to store call IDs from Google Sheet
    $call_id_array = array();

    //Connect to spreadsheet URL and populate $callid_array
    if (($handle = fopen($spreadsheet_url, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            array_push($call_id_array, $data[1]);
        }
        fclose($handle);

        //Check if returned call ID from ResponseTap is not in Google Sheets
        if(!in_array($callID, $call_id_array)){

            //Connect to ResponseTap to get call details for each ID
            $call_api_url = 'https://dashboard.responsetap.com/api/1/accounts/RESPONSETAP_ACCOUNT_NUMBER/calls/' . $callID;
            $call_api_url_response = curl_connection($call_api_url);
            $calldetails = json_decode($call_api_url_response, true);

            /*
                Populate call details into Google Sheets

                STEPS TO GET $google_post_url
                - Create a Google Sheets with the correct headers
                - Go to Tools>Script Editor and use https://github.com/aliergal/google-sheets-script file and follow the instructions

            */
            $google_post_url = 'YOUR_SCRIPT_URL';
            $google_post_data = "ID=".$callID."&CampaignName=".$calldetails["campaignName"]."&MediumName=".$calldetails["mediumName"]."&CustomerNumber=".$calldetails["customerNumber"]."&Disposition=".$calldetails["disposition"];
            $google_response = curl_connection($google_post_url, $google_post_data, "POST");

            //Delay 1 second inbetween iterations to prevent Google Sheets error
            sleep(1);
        }
        
    }
    
}

//Main function to create curl connection. Return response from API in JSON format
function curl_connection($url, $data = "", $type = "GET"){
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $type,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            "accept: application/json",
            "authorization: Basic YOUR_API_AUTHENTICATION",
            "cache-control: no-cache",
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
}


