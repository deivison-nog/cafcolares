<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
include 'db.php';

// Carregar os usuários com nível de acesso 'usuario'
$stmt = $pdo->prepare('SELECT id, usuario FROM usuarios WHERE nivel_acesso = ?');
$stmt->execute(['usuario']);
$usuarios = $stmt->fetchAll();

// Tratamento da confirmação de distribuição
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'confirm') {
    $distribuicoes = json_decode($_POST['distribuicoes'], true);
    foreach ($distribuicoes as $distribuicao) {
        $medicamento_id = $distribuicao['medicamento_id'];
        $estabelecimento_id = $distribuicao['estabelecimento_id'];
        $quantidade = $distribuicao['quantidade'];
        $data_distribuicao = date('Y-m-d');
        $hora_distribuicao = date('H:i:s');

        // Atualizar a quantidade no estoque do administrador
        $stmt = $pdo->prepare('UPDATE medicamentos SET quantidade = quantidade - ? WHERE id = ? AND quantidade >= ?');
        if ($stmt->execute([$quantidade, $medicamento_id, $quantidade])) {
            // Registrar a distribuição
            $stmt = $pdo->prepare('INSERT INTO distribuicao (medicamento_id, estabelecimento_id, quantidade, data_distribuicao, hora_distribuicao) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$medicamento_id, $estabelecimento_id, $quantidade, $data_distribuicao, $hora_distribuicao]);

            // Copiar os dados do medicamento
            $stmt = $pdo->prepare('SELECT * FROM medicamentos WHERE id = ?');
            $stmt->execute([$medicamento_id]);
            $medicamento = $stmt->fetch();

            // Buscar o nome do usuário
            $stmt = $pdo->prepare('SELECT usuario FROM usuarios WHERE id = ?');
            $stmt->execute([$estabelecimento_id]);
            $usuario = $stmt->fetchColumn();

            if ($medicamento && $usuario) {
                // Verificar se o medicamento já está no estabelecimento
                $stmt = $pdo->prepare('SELECT quantidade FROM medicamentos WHERE codigo_barras = ? AND estabelecimento = ?');
                $stmt->execute([$medicamento['codigo_barras'], $usuario]);
                $existing_medicamento = $stmt->fetch();

                if ($existing_medicamento) {
                    // Atualizar a quantidade se o medicamento já estiver no estabelecimento
                    $stmt = $pdo->prepare('UPDATE medicamentos SET quantidade = quantidade + ? WHERE codigo_barras = ? AND estabelecimento = ?');
                    $stmt->execute([$quantidade, $medicamento['codigo_barras'], $usuario]);
                } else {
                    // Inserir um novo registro se o medicamento não estiver no estabelecimento
                    $stmt = $pdo->prepare('INSERT INTO medicamentos (codigo_barras, medicamento, apresentacao, marca, lote, validade, fornecedor, quantidade, estabelecimento, categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([
                        $medicamento['codigo_barras'],
                        $medicamento['medicamento'],
                        $medicamento['apresentacao'],
                        $medicamento['marca'],
                        $medicamento['lote'],
                        $medicamento['validade'],
                        $medicamento['fornecedor'],
                        $quantidade,
                        $usuario,
                        $medicamento['categoria']
                    ]);
                }
            }
        }
    }

    // Após o processamento, redirecione o usuário para a página de última distribuição
    header('Location: ultima_distribuicao.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribuição de Medicamentos - CAF</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        #medicamento-list {
            position: absolute;
            border: 1px solid #ccc;
            background-color: #fff;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            width: 300px;
        }
        #medicamento-list div {
            padding: 10px;
            cursor: pointer;
        }
        #medicamento-list div:hover {
            background-color: #ddd;
        }
    </style>
    <script>
        let distribuicoes = [];

        function fetchMedicamentos(query) {
            if (query.length == 0) {
                document.getElementById('medicamento-list').innerHTML = '';
                return;
            }
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById('medicamento-list').innerHTML = this.responseText;
                }
            };
            xhr.open('GET', 'fetch_medicamentos.php?q=' + query, true);
            xhr.send();
        }

        function fetchMedicamentoQuantidade(medicamentoId) {
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const response = JSON.parse(this.responseText);
                    document.getElementById('apresentacao').value = response.apresentacao;
                    document.getElementById('marca').value = response.marca;
                    document.getElementById('lote').value = response.lote;
                    document.getElementById('validade').value = response.validade;
                    document.getElementById('quantidade').value = response.quantidade;
                    document.getElementById('categoria').value = response.categoria; // Adicionando a categoria
                }
            };
            xhr.open('GET', 'fetch_medicamento_quantidade.php?medicamento_id=' + medicamentoId, true);
            xhr.send();
        }

        function selectMedicamento(id, name) {
            document.getElementById('medicamento').value = name;
            document.getElementById('medicamento-id').value = id;
            document.getElementById('medicamento-list').innerHTML = '';
            fetchMedicamentoQuantidade(id);
        }

        function addToList() {
            const medicamentoId = document.getElementById('medicamento-id').value;
            const medicamentoName = document.getElementById('medicamento').value;
            const apresentacao = document.getElementById('apresentacao').value;
            const marca = document.getElementById('marca').value;
            const lote = document.getElementById('lote').value;
            const validade = document.getElementById('validade').value;
            const categoria = document.getElementById('categoria').value; // Incluindo categoria
            const estabelecimentoId = document.getElementById('estabelecimento').value;
            const estabelecimentoName = document.getElementById('estabelecimento').options[document.getElementById('estabelecimento').selectedIndex].text;
            const quantidade = document.getElementById('quantidade').value;

            if (medicamentoId && medicamentoName && apresentacao && marca && lote && validade && categoria && estabelecimentoId && quantidade) {
                distribuicoes.push({
                    medicamento_id: medicamentoId,
                    medicamento_name: medicamentoName,
                    apresentacao: apresentacao,
                    marca: marca,
                    lote: lote,
                    validade: validade,
                    categoria: categoria, // Adicionando categoria
                    estabelecimento_id: estabelecimentoId,
                    estabelecimento_name: estabelecimentoName,
                    quantidade: quantidade
                });

                updateDistribuicaoList();
                document.getElementById('distribuicao-form').reset();
            } else {
                alert('Por favor, preencha todos os campos.');
            }
        }

        function updateDistribuicaoList() {
            const tableBody = document.getElementById('distribuicao-table-body');
            tableBody.innerHTML = '';
            distribuicoes.forEach((distribuicao, index) => {
                const row = tableBody.insertRow();
                row.insertCell(0).textContent = distribuicao.medicamento_name;
                row.insertCell(1).textContent = distribuicao.apresentacao;
                row.insertCell(2).textContent = distribuicao.marca;
                row.insertCell(3).textContent = distribuicao.lote;
                row.insertCell(4).textContent = distribuicao.validade;
                row.insertCell(5).textContent = distribuicao.quantidade;
                row.insertCell(6).textContent = distribuicao.categoria; // Mostrando categoria
                row.insertCell(7).textContent = distribuicao.estabelecimento_name;
                row.insertCell(8).innerHTML = `<button type="button" onclick="removeFromList(${index})">Remover</button>`;
            });
        }

        function removeFromList(index) {
            distribuicoes.splice(index, 1);
            updateDistribuicaoList();
        }

        function confirmDistribuicao() {
            if (distribuicoes.length == 0) {
                alert('A lista de distribuição está vazia.');
                return false;
            }
            if (confirm('Deseja realmente confirmar essa distribuição?')) {
                document.getElementById('distribuicoes-input').value = JSON.stringify(distribuicoes);
                return true;
            }
            return false;
        }
    </script>
</head>
<body>
    <?php include 'includes/head.php'; ?>
    <div class="container">
        <?php include 'includes/menu_lateral.php'; ?>
        <div class="main-content">
            <h2>Distribuição de Medicamentos</h2>
            <?php if (isset($success)) echo "<p>$success</p>"; ?>

            <form id="distribuicao-form">
                <div>
                    <label for="medicamento">Medicamento/Produto:</label>
                    <input type="text" id="medicamento" name="medicamento" onkeyup="fetchMedicamentos(this.value)" required>
                    <div id="medicamento-list"></div>
                    <input type="hidden" id="medicamento-id" name="medicamento_id">
                </div>
                <div>
                    <label for="apresentacao">Apresentação:</label>
                    <input type="text" id="apresentacao" name="apresentacao" readonly>
                </div>
                <div>
                    <label for="marca">Marca:</label>
                    <input type="text" id="marca" name="marca" readonly>
                </div>
                <div>
                    <label for="lote">Lote:</label>
                    <input type="text" id="lote" name="lote" readonly>
                </div>
                <div>
                    <label for="validade">Validade:</label>
                    <input type="text" id="validade" name="validade" readonly>
                </div>
                <div>
                    <label for="categoria">Categoria:</label> <!-- Campo para categoria -->
                    <input type="text" id="categoria" name="categoria" readonly>
                </div>
                <div>
                    <label for="estabelecimento">Estabelecimento:</label>
                    <select id="estabelecimento" name="estabelecimento" required>
                        <option value="">Selecione o estabelecimento</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?php echo htmlspecialchars($usuario['id']); ?>"><?php echo htmlspecialchars($usuario['usuario']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="quantidade">Quantidade:</label>
                    <input type="number" id="quantidade" name="quantidade" required>
                </div>
                <button type="button" onclick="addToList()">Adicionar à Lista</button>
            </form>

            <h3>Lista de Distribuição</h3>
            <table>
                <thead>
                    <tr>
                        <th>Medicamento/Produto</th>
                        <th>Apresentação</th>
                        <th>Marca</th>
                        <th>Lote</th>
                        <th>Validade</th>
                        <th>Quantidade</th>
                        <th>Categoria</th> <!-- Categoria adicionada à tabela -->
                        <th>Estabelecimento</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="distribuicao-table-body">
                    <!-- Aqui serão inseridas as linhas da tabela dinamicamente -->
                </tbody>
            </table>

            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return confirmDistribuicao();">
                <input type="hidden" name="action" value="confirm">
                <input type="hidden" id="distribuicoes-input" name="distribuicoes">
                <button type="submit">Confirmar Distribuição</button>
            </form>
        </div>
    </div>
    <?php include 'includes/foot.php'; ?>
</body>
</html>
