<pre>
    <code class="language-php php">
&lt;?php
    $data = [
        'api_key' => '{{$apiKey}}',
        'sender' => 'Sender',
        'number' => 'receiver',
        'message' => 'Your message',
        'footer' => 'Your footer message',
        'name' => 'Name List ',
        'title' => 'Title List ',
        'list1' => 'list 1 ', // REQUIRED ( list minimal 1 )
        'list2' => 'list 2', // OPTIONAL
        'list3' => 'list 3', // OPTIONAL
        'list4' => 'list 4', // OPTIONAL
        'list5' => 'list 5', // OPTIONAL
    ];
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => '{{url('/')}}/send-list',
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