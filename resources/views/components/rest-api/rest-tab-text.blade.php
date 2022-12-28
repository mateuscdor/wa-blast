<pre>
    <code class="language-php php">
&lt;?php
    $data = [
        'api_key' => '{{$apiKey}}',
        'sender' => 'Sender',
        'number' => 'receiver',
        'message' => 'Your message'
    ];
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => '{{url('/')}}/send-message',
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
        )
    )

    $response = curl_exec($curl);

    curl_close($curl);
    echo $response;
?&gt;
    </code>
</pre>