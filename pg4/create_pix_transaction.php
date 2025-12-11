<?php

// =================================================================================================
// CONFIGURAÇÕES DA API E AUTENTICAÇÃO
// ATENÇÃO: Substitua as credenciais abaixo pelas suas credenciais reais da HunterPay.
// O usuário forneceu as seguintes credenciais para o teste:
// ID (Username): d7bec9d5-cb80-41ae-b7fd-114615f22eb3
// Secret (Password): hu_live_NERPWnoycUw0eks5aVZs
// =================================================================================================
$api_url = "https://api.hunterpayments.com.br/functions/v1/transactions";
$username = "d7bec9d5-cb80-41ae-b7fd-114615f22eb3"; // Substitua pelo seu ID
$password = "hu_live_NERPWnoycUw0eks5aVZs"; // Substitua pelo seu Secret

// =================================================================================================
// DADOS DA TRANSAÇÃO
// Valor Fixo: R$ 10,00 (em centavos)
// CPF Fixo: 18219822821
// =================================================================================================
$amount_cents = 1000; // R$ 10,00 em centavos

$data = [
    "customer" => [
        "name" => "Cliente Teste", // Nome de exemplo
        "email" => "cliente.teste@exemplo.com", // Email de exemplo
        "phone" => "11999999999", // Telefone de exemplo
        "document" => [
            "number" => "18219822821", // CPF Fixo solicitado
            "type" => "CPF"
        ]
    ],
    "paymentMethod" => "PIX",
    "pix" => [
        "expiresInDays" => 1 // PIX válido por 1 dia
    ],
    "items" => [
        [
            "description" => "Pagamento PIX de R$ 10,00",
            "amount" => $amount_cents
        ]
    ],
    "amount" => $amount_cents
];

// Codifica os dados para JSON
$json_data = json_encode($data);

// =================================================================================================
// REQUISIÇÃO cURL
// =================================================================================================

// Inicializa o cURL
$ch = curl_init($api_url);

// Configura as opções do cURL
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode("$username:$password") // Autenticação Basic Auth
]);

// Executa a requisição
$response = curl_exec($ch);

// Verifica por erros
if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
    echo "Erro cURL: " . $error_msg . "\n";
}

// Obtém o código de status HTTP
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Fecha a sessão cURL
curl_close($ch);

// Decodifica a resposta JSON
$response_data = json_decode($response, true);

// =================================================================================================
// TRATAMENTO DA RESPOSTA
// =================================================================================================

echo "Status HTTP: " . $http_code . "\n";

if ($http_code == 200 && isset($response_data['transaction'])) {
    echo "Transação PIX criada com sucesso!\n";
    echo "ID da Transação: " . $response_data['transaction']['id'] . "\n";
    echo "Valor: R$ " . number_format($response_data['transaction']['amount'] / 100, 2, ',', '.') . "\n";
    
    // Exibe o código PIX (copia e cola) e o QR Code (se disponíveis)
    if (isset($response_data['transaction']['pix']['qrCodeText'])) {
        echo "\n--- PIX Copia e Cola ---\n";
        echo $response_data['transaction']['pix']['qrCodeText'] . "\n";
    }
    
    if (isset($response_data['transaction']['pix']['qrCodeImage'])) {
        echo "\n--- URL da Imagem do QR Code ---\n";
        echo $response_data['transaction']['pix']['qrCodeImage'] . "\n";
    }
    
} else {
    echo "Falha ao criar a transação PIX.\n";
    echo "Resposta Completa da API:\n";
    print_r($response_data);
}

?>
