<?php
include 'db.php';

date_default_timezone_set('America/Sao_Paulo'); // Garante que o horário será o de Brasília

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['registrar_saida'])) {
        $itens = json_decode($_POST['itens'], true);

        foreach ($itens as $item) {
            $medicamento_id = $item['medicamento_id'];
            $quantidade = $item['quantidade'];
            $paciente = $item['paciente'];
            $data_distribuicao = date('Y-m-d');
            $hora_distribuicao = date('H:i:s');

            // Obtendo o ID do estabelecimento do usuário autenticado
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
            $stmt->execute([$_SESSION['usuario']]);
            $estabelecimento_id = $stmt->fetchColumn();

            if (!$estabelecimento_id) {
                echo json_encode(["erro" => "Estabelecimento não encontrado para o usuário autenticado."]);
                exit;
            }

            try {
                // Verifica se o paciente já existe
                $stmt = $pdo->prepare('SELECT id FROM pacientes WHERE nome = ?');
                $stmt->execute([$paciente]);
                $paciente_id = $stmt->fetchColumn();

                // Se não existe, cadastra novo paciente (aceita data_nascimento nula)
                if (!$paciente_id) {
                    $data_nascimento = !empty($item['data_nascimento']) ? $item['data_nascimento'] : null;
                    $endereco = !empty($item['endereco']) ? $item['endereco'] : null;

                    $stmt = $pdo->prepare('INSERT INTO pacientes (nome, data_nascimento, endereco) VALUES (?, ?, ?)');
                    $stmt->execute([$paciente, $data_nascimento, $endereco]);
                    $paciente_id = $pdo->lastInsertId();
                }

                // Insere distribuição
                $stmt = $pdo->prepare('INSERT INTO distribuicao_pacientes (medicamento_id, paciente_id, quantidade, data_distribuicao, hora_distribuicao, estabelecimento_id) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$medicamento_id, $paciente_id, $quantidade, $data_distribuicao, $hora_distribuicao, $estabelecimento_id]);

                // Atualiza estoque
                $stmt = $pdo->prepare('UPDATE medicamentos SET quantidade = quantidade - ? WHERE id = ?');
                $stmt->execute([$quantidade, $medicamento_id]);

            } catch (PDOException $e) {
                echo json_encode(["erro" => $e->getMessage()]);
                exit;
            }
        }

        echo json_encode(["sucesso" => "Saída registrada com sucesso."]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saída de Medicamentos - CAF</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .suggestion-box { border: 1px solid #ccc; border-top: none; max-height: 200px; overflow-y: auto; position: absolute; width: calc(100% - 20px); background-color: white; z-index: 1000;}
        .suggestion-item { padding: 10px; cursor: pointer;}
        .suggestion-item:hover { background-color: #f0f0f0;}
        table { width: 100%; border-collapse: collapse; margin-top: 20px;}
        table, th, td { border: 1px solid black;}
        th, td { padding: 8px; text-align: left;}
        th { background-color: #f2f2f2;}
    </style>
    <script>
        let listaItens = [];

        function fetchSuggestions(query, type) {
            if (query.length == 0) {
                document.getElementById(type + '-suggestion-box').innerHTML = '';
                return;
            }
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById(type + '-suggestion-box').innerHTML = this.responseText;
                }
            };
            xhr.open('GET', 'fetch_suggestions_user.php?q=' + query + '&type=' + type, true);
            xhr.send();
        }

        function selectItem(id, name, type, extraData = {}) {
            document.getElementById(type).value = name;
            document.getElementById(type + '-id').value = id;
            document.getElementById(type + '-suggestion-box').innerHTML = '';
            if (type === 'medicamento') {
                document.getElementById('quantidade').value = extraData.quantidade;
            }
            if (type === 'paciente') {
                if (extraData.data_nascimento) {
                    document.getElementById('data_nascimento').value = extraData.data_nascimento;
                }
                if (extraData.endereco) {
                    document.getElementById('endereco').value = extraData.endereco;
                }
            }
        }

        function incluirNaLista() {
            const medicamento_id = document.getElementById('medicamento-id').value;
            const medicamento = document.getElementById('medicamento').value;
            const quantidade = document.getElementById('quantidade').value;
            const paciente = document.getElementById('paciente').value;
            const data_nascimento = document.getElementById('data_nascimento').value;
            const endereco = document.getElementById('endereco').value;

            if (!medicamento_id || !paciente || !quantidade) {
                alert('Preencha todos os campos obrigatórios.');
                return;
            }

            // Verifica a quantidade em estoque
            fetch('verificar_estoque.php?id=' + medicamento_id)
                .then(response => response.json())
                .then(data => {
                    if (data.quantidade < quantidade) {
                        alert('Quantidade em estoque insuficiente.');
                    } else {
                        const item = {
                            medicamento_id,
                            medicamento,
                            quantidade,
                            paciente,
                            data_nascimento,
                            endereco
                        };
                        listaItens.push(item);
                        atualizarTabela();
                    }
                })
                .catch(error => console.error('Erro ao verificar estoque:', error));
        }

        function atualizarTabela() {
            const tabela = document.getElementById('tabela-itens');
            tabela.innerHTML = '';
            listaItens.forEach((item, index) => {
                const row = tabela.insertRow();
                row.insertCell(0).innerText = item.medicamento;
                row.insertCell(1).innerText = item.quantidade;
                row.insertCell(2).innerText = item.paciente;
                row.insertCell(3).innerHTML = `<button onclick="removerItem(${index})">Remover</button>`;
            });
        }

        function removerItem(index) {
            listaItens.splice(index, 1);
            atualizarTabela();
        }

        function registrarSaida() {
            if (listaItens.length === 0) {
                alert('Adicione pelo menos um item para registrar a saída.');
                return;
            }
            if (confirm('Deseja realmente registrar essa saída?')) {
                const data = new FormData();
                data.append('registrar_saida', 'true');
                data.append('itens', JSON.stringify(listaItens));

                fetch(window.location.href, {
                    method: 'POST',
                    body: data
                })
                .then(response => response.json())
                .then(result => {
                    if (result.erro) {
                        console.error('Erro: ' + result.erro);
                    } else {
                        console.log(result.sucesso);
                        window.location.href = 'ultima_saida_user.php';
                    }
                })
                .catch(error => console.error('Erro de rede:', error));
            }
        }
    </script>
</head>
<body>
    <?php include 'includes/head.php'; ?>
    <div class="container">
        <?php include 'includes/menu_lateral.php'; ?>
        <div class="main-content">
            <h2>Saída de Medicamentos</h2>
            <form id="form-saida" method="post">
                <div>
                    <label for="medicamento">Medicamento/Produto:</label>
                    <input type="text" id="medicamento" name="medicamento" onkeyup="fetchSuggestions(this.value, 'medicamento')" required>
                    <div id="medicamento-suggestion-box" class="suggestion-box"></div>
                    <input type="hidden" id="medicamento-id" name="medicamento_id">
                </div>
                <div>
                    <label for="paciente">Paciente:</label>
                    <input type="text" id="paciente" name="paciente" onkeyup="fetchSuggestions(this.value, 'paciente')" required>
                    <div id="paciente-suggestion-box" class="suggestion-box"></div>
                    <input type="hidden" id="paciente-id" name="paciente_id">
                </div>
                <div>
                    <label for="data_nascimento">Data de Nascimento:</label>
                    <input type="date" id="data_nascimento" name="data_nascimento">
                </div>
                <div>
                    <label for="endereco">Endereço:</label>
                    <input type="text" id="endereco" name="endereco">
                </div>
                <div>
                    <label for="quantidade">Quantidade:</label>
                    <input type="number" id="quantidade" name="quantidade" min="1" required>
                </div>
                <button type="button" onclick="incluirNaLista()">Incluir na Lista</button>
                <h3>Itens na Lista</h3>
                <table id="tabela-itens">
                    <thead>
                        <tr>
                            <th>Medicamento</th>
                            <th>Quantidade</th>
                            <th>Paciente</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <button type="button" onclick="registrarSaida()">Registrar Saída</button>
            </form>
        </div>
    </div>
</body>
</html>
