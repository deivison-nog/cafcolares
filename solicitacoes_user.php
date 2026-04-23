<?php
session_start();

date_default_timezone_set('America/Sao_Paulo'); // Garante que o horário será o de Brasília

// Verifique se o usuário está logado e se o nível de acesso é 'usuario'
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

// Verifique se o ID do usuário está definido na sessão
if (!isset($_SESSION['usuario_id'])) {
    echo "Usuário não encontrado no banco de dados.";
    exit;
}

// Conexão com o banco de dados
include 'db.php';

// Obter o ID do usuário logado
$usuario_id = $_SESSION['usuario_id'];

// Verificar se o usuário existe no banco de dados antes de prosseguir
$sql_check_user = 'SELECT id FROM usuarios WHERE id = ?';
$stmt_check_user = $pdo->prepare($sql_check_user);
$stmt_check_user->execute([$usuario_id]);

// Se o usuário não existir, redireciona para a página inicial
if ($stmt_check_user->rowCount() == 0) {
    echo "Usuário não encontrado no banco de dados.";
    exit;
}

// Consulta SQL para carregar o estoque de medicamentos
$sql = 'SELECT * FROM medicamentos WHERE estabelecimento = ?';
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_id]);
$medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cria um array associativo em JavaScript para facilitar a verificação no frontend
$medicamentos_json = json_encode($medicamentos);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitações de Medicamentos</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Estilo adicional para as sugestões */
        #suggestions {
            border: 1px solid #ccc;
            max-height: 200px;
            overflow-y: auto;
            position: absolute;
            width: 100%;
            background-color: white;
            z-index: 1000;
            display: none;
        }

        #suggestions div {
            padding: 8px;
            cursor: pointer;
        }

        #suggestions div:hover {
            background-color: #f0f0f0;
        }

        #medicamento-list th, #medicamento-list td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ccc;
        }

        button {
            padding: 10px;
            background-color: #000080;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
        }

        button:hover {
            background-color: #45a049;
        }

        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include 'includes/head.php'; ?>
    <div class="container">
        <?php include 'includes/menu_lateral.php'; ?>
        <div class="main-content">
            <h2>Solicitações de Medicamentos</h2>

            <!-- Campo de pesquisa e quantidade -->
            <form id="medicamento-form" onsubmit="event.preventDefault(); addToList();">
                <label for="pesquisa">Buscar Medicamento:</label>
                <input type="text" id="pesquisa" name="pesquisa" autocomplete="off" onkeyup="fetchMedicamentos(this.value)">
                <div id="suggestions"></div>

                <label for="quantidade">Quantidade:</label>
                <input type="number" id="quantidade" name="quantidade" min="1" required>

                <button type="submit">Adicionar à lista</button>
            </form>

            <!-- Tabela de medicamentos adicionados -->
            <h3>Medicamentos Selecionados</h3>
            <table id="medicamento-list">
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Quantidade Solicitada</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody id="lista-medicamentos">
                    <!-- Linhas adicionadas dinamicamente -->
                </tbody>
            </table>

            <button id="enviar-solicitacao" onclick="enviarSolicitacao()">Enviar Solicitação</button>
        </div>
    </div>
    <?php include 'includes/foot.php'; ?>

    <script>
        const medicamentosEstoque = <?php echo $medicamentos_json; ?>;

        // Função para buscar os medicamentos com base no input digitado
        function fetchMedicamentos(query) {
            const suggestions = document.getElementById('suggestions');
            if (!query) {
                suggestions.style.display = 'none';
                suggestions.innerHTML = '';
                return;
            }

            fetch(`fetch_medicamentos_user.php?q=${encodeURIComponent(query)}`)
                .then(response => response.text())
                .then(data => {
                    suggestions.innerHTML = data;
                    suggestions.style.display = 'block';
                })
                .catch(error => console.error('Erro ao buscar medicamentos:', error));
        }

        // Selecionar um medicamento da lista de sugestões
        function selectMedicamento(id, nome) {
            const pesquisa = document.getElementById('pesquisa');
            pesquisa.value = nome;
            pesquisa.setAttribute('data-id', id);
            document.getElementById('suggestions').style.display = 'none';
        }

        // Adicionar medicamento à tabela de solicitações
        function addToList() {
            const pesquisa = document.getElementById('pesquisa');
            const quantidade = document.getElementById('quantidade').value;
            const lista = document.getElementById('lista-medicamentos');

            const medicamentoId = pesquisa.getAttribute('data-id');
            if (!medicamentoId || !quantidade) {
                alert('Preencha todos os campos antes de adicionar.');
                return;
            }

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${pesquisa.value}</td>
                <td>${quantidade}</td>
                <td><button onclick="removeRow(this)">Remover</button></td>
            `;
            row.setAttribute('data-id', medicamentoId);
            lista.appendChild(row);

            pesquisa.value = '';
            pesquisa.removeAttribute('data-id');
            document.getElementById('quantidade').value = '';
        }

        function removeRow(button) {
            const row = button.parentElement.parentElement;
            row.remove();
        }

        function enviarSolicitacao() {
            const lista = document.getElementById('lista-medicamentos');
            const rows = lista.querySelectorAll('tr');

            if (!rows.length) {
                alert('Adicione medicamentos antes de enviar a solicitação.');
                return;
            }

            const data = Array.from(rows).map(row => ({
                medicamento_id: row.getAttribute('data-id'),
                quantidade: row.children[1].textContent
            }));

            // Adicionar o usuario_id, que vem da sessão
            const usuario_id = <?php echo json_encode($_SESSION['usuario_id']); ?>;  // Passar o valor da sessão no JavaScript

            fetch('salvar_solicitacao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ solicitacoes: data, usuario_id: usuario_id })
            })
            .then(response => response.text())
            .then(data => {
                console.log('Resposta do servidor:', data);
                try {
                    const result = JSON.parse(data);
                    if (result.success) {
                        // Perguntar ao usuário se realmente deseja fazer a solicitação
                        if (confirm('Deseja realmente confirmar a solicitação?')) {
                            // Redirecionar para ultima_solicitacao_user.php se a solicitação for bem-sucedida
                            window.location.href = 'ultima_solicitacao_user.php';
                        } else {
                            alert('Solicitação cancelada.');
                        }
                    } else {
                        alert('Erro ao enviar a solicitação: ' + result.error);
                    }
                } catch (e) {
                    console.error('Erro ao processar resposta JSON:', e);
                    alert('Erro ao processar a resposta do servidor.');
                }
            })
            .catch(error => {
                console.error('Erro ao enviar solicitação:', error);
                alert('Erro ao enviar a solicitação.');
            });
        }
    </script>
</body>
</html>
