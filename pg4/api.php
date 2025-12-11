<?php

// =================================================================================================
// API PIX - HUNTERPAY - INTEGRAÇÃO COMPLETA
// Recebe dados dinâmicos do cliente e valor para gerar transação PIX
// =================================================================================================

header('Content-Type: application/json');

// Configurações da API
$api_url = "https://api.hunterpayments.com.br/functions/v1/transactions";

// Credenciais codificadas em Base64
$auth_header = "Basic aHVfbGl2ZV9ORVJQV25veWNVdzBla3M1YVZaczpkN2JlYzlkNS1jYjgwLTQxYWUtYjdmZC0xMTQ2MTVmMjJlYjM=";

// Receber dados via POST
$input = json_decode(file_get_contents('php://input'), true);

// Validar dados obrigatórios
if (!$input || !isset($input['valor']) || !isset($input['cliente'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Dados incompletos. Valor e dados do cliente são obrigatórios."
    ]);
    exit;
}

$valor_centavos = (int)$input['valor'];
$cliente = $input['cliente'];

// Validar cliente
if (!isset($cliente['nome']) || !isset($cliente['email']) || !isset($cliente['cpf'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Dados do cliente incompletos."
    ]);
    exit;
}

// Preparar dados da transação PIX
$data = [
    "paymentMethod" => "PIX",
    "amount" => $valor_centavos,
    "customer" => [
        "name" => $cliente['nome'],
        "phone" => $cliente['celular'] ?? "11999999999",
        "email" => $cliente['email'],
        "document" => [
            "type" => "CPF",
            "number" => preg_replace('/\D/', '', $cliente['cpf']) // Remove caracteres não numéricos
        ],
        "address" => [
            "street" => $cliente['rua'] ?? "Rua Teste",
            "streetNumber" => $cliente['numero'] ?? "123",
            "zipCode" => preg_replace('/\D/', '', $cliente['cep'] ?? "01001000"),
            "neighborhood" => $cliente['bairro'] ?? "Centro",
            "city" => $cliente['cidade'] ?? "São Paulo",
            "state" => $cliente['estado'] ?? "SP",
            "country" => "BR"
        ]
    ],
    "items" => [
        [
            "title" => "Pagamento PIX R$ " . number_format($valor_centavos / 100, 2, ',', '.'),
            "unitPrice" => $valor_centavos,
            "quantity" => 1,
            "tangible" => false
        ]
    ],
    "pix" => [
        "expiresIn" => 3600 // 1 hora
    ]
];

// Codifica os dados para JSON
$json_data = json_encode($data);

// =================================================================================================
// REQUISIÇÃO cURL
// =================================================================================================

$ch = curl_init($api_url);

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: ' . $auth_header
]);

// Executa a requisição
$response = curl_exec($ch);

// Verifica por erros cURL
if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Erro cURL: " . $error_msg
    ]);
    curl_close($ch);
    exit;
}

// Obtém o código de status HTTP
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Decodifica a resposta JSON
$response_data = json_decode($response, true);

// =================================================================================================
// TRATAMENTO DA RESPOSTA
// =================================================================================================

if ($http_code == 200) {
    // Sucesso - retorna os dados da transação
    echo json_encode([
        "success" => true,
        "transaction" => $response_data
    ]);
} else {
    // Falha - retorna o erro
    http_response_code($http_code);
    echo json_encode([
        "success" => false,
        "error" => "Falha ao criar a transação PIX",
        "http_code" => $http_code,
        "response" => $response_data
    ]);
}

?>
