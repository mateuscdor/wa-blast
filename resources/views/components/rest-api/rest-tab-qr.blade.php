<pre>
    <code class="language-php php">
&lt;?php
    $data = [
        'api_key' => '{{$apiKey}}',
        'number' => 'Number', // the number you want to connect, will be added to the database if it is not registered.
    ];
    $curl = curl_init();
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => '{{url('/')}}/generate-qr',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    echo $response;
?&gt;
    </code>
</pre>