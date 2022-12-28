<pre>
    <code class="language-php php">
&lt;?php
    $data = [
        'api_key' => '{{$apiKey}}',
        'sender' => 'Sender',
        'number' => 'receiver',
        'message' => 'Your message',
        'footer' => 'Your footer message',
        'image' => 'URL image ', // OPTIONAL
        'template1' => 'template 1 ', // REQUIRED ( template minimal 1 )
        'template2' => 'template 2', // OPTIONAL
        'template3' => 'template 3', // OPTIONAL
    ];
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => '{{url('/')}}/send-template',
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
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    echo $response;
?&gt;
    </code>
</pre>