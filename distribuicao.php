<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'db.php';

$stmt = $pdo->prepare('SELECT id, usuario FROM usuarios WHERE nivel_acesso = ?');
$stmt->execute(['usuario']);
$usuarios = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'confirm') {
    $distribuicoes = json_decode($_POST['distribuicoes'], true);
    foreach ($distribuicoes as $distribuicao) {
        $medicamento_id = $distribuicao['medicamento_id'];
        $estabelecimento_id = $distribuicao['estabelecimento_id'];
        $quantidade = $distribuicao['quantidade'];
        $data_distribuicao = date('Y-m-d');
        $hora_distribuicao = date('H:i:s');

        $stmt = $pdo->prepare('UPDATE medicamentos SET quantidade = quantidade - ? WHERE id = ? AND quantidade >= ?');
        if ($stmt->execute([$quantidade, $medicamento_id, $quantidade])) {
            $stmt = $pdo->prepare('INSERT INTO distribuicao (medicamento_id, estabelecimento_id, quantidade, data_distribuicao, hora_distribuicao) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$medicamento_id, $estabelecimento_id, $quantidade, $data_distribuicao, $hora_distribuicao]);

            $stmt = $pdo->prepare('SELECT * FROM medicamentos WHERE id = ?');
            $stmt->execute([$medicamento_id]);
            $medicamento = $stmt->fetch();

            $stmt = $pdo->prepare('SELECT usuario FROM usuarios WHERE id = ?');
            $stmt->execute([$estabelecimento_id]);
            $usuario = $stmt->fetchColumn();

            if ($medicamento && $usuario) {
                $stmt = $pdo->prepare('SELECT quantidade FROM medicamentos WHERE codigo_barras = ? AND estabelecimento = ?');
                $stmt->execute([$medicamento['codigo_barras'], $usuario]);
                $existing_medicamento = $stmt->fetch();

                if ($existing_medicamento) {
                    $stmt = $pdo->prepare('UPDATE medicamentos SET quantidade = quantidade + ? WHERE codigo_barras = ? AND estabelecimento = ?');
                    $stmt->execute([$quantidade, $medicamento['codigo_barras'], $usuario]);
                } else {
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

    header('Location: ultima_distribuicao.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Distribuição de Medicamentos – CAF</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/style.css">
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
          document.getElementById('categoria').value = response.categoria;
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
      const categoria = document.getElementById('categoria').value;
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
          categoria: categoria,
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
        row.insertCell(6).textContent = distribuicao.categoria;
        row.insertCell(7).textContent = distribuicao.estabelecimento_name;
        const btnCell = row.insertCell(8);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-danger';
        const icon = document.createElement('i');
        icon.className = 'bi bi-trash';
        btn.appendChild(icon);
        btn.onclick = function() { removeFromList(index); };
        btnCell.appendChild(btn);
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
<div id="wrapper" class="d-flex">
  <?php include 'includes/menu_lateral.php'; ?>
  <div class="main-content flex-grow-1">
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-truck fs-3 me-2 text-primary"></i>
      <h2 class="mb-0">Distribuição de Medicamentos</h2>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-search me-2"></i>Selecionar Medicamento</h5>
      </div>
      <div class="card-body">
        <form id="distribuicao-form">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="medicamento" class="form-label">Medicamento/Produto</label>
              <div class="position-relative">
                <input type="text" class="form-control" id="medicamento" name="medicamento"
                  onkeyup="fetchMedicamentos(this.value)" autocomplete="off" required>
                <div id="medicamento-list" class="suggestion-box"></div>
                <input type="hidden" id="medicamento-id" name="medicamento_id">
              </div>
            </div>
            <div class="col-md-6">
              <label for="estabelecimento" class="form-label">Estabelecimento</label>
              <select class="form-select" id="estabelecimento" name="estabelecimento" required>
                <option value="">Selecione o estabelecimento</option>
                <?php foreach ($usuarios as $usuario): ?>
                  <option value="<?php echo htmlspecialchars($usuario['id']); ?>">
                    <?php echo htmlspecialchars($usuario['usuario']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="apresentacao" class="form-label">Apresentação</label>
              <input type="text" class="form-control" id="apresentacao" name="apresentacao" readonly>
            </div>
            <div class="col-md-4">
              <label for="marca" class="form-label">Marca</label>
              <input type="text" class="form-control" id="marca" name="marca" readonly>
            </div>
            <div class="col-md-4">
              <label for="lote" class="form-label">Lote</label>
              <input type="text" class="form-control" id="lote" name="lote" readonly>
            </div>
            <div class="col-md-4">
              <label for="validade" class="form-label">Validade</label>
              <input type="text" class="form-control" id="validade" name="validade" readonly>
            </div>
            <div class="col-md-4">
              <label for="categoria" class="form-label">Categoria</label>
              <input type="text" class="form-control" id="categoria" name="categoria" readonly>
            </div>
            <div class="col-md-4">
              <label for="quantidade" class="form-label">Quantidade</label>
              <input type="number" class="form-control" id="quantidade" name="quantidade" required>
            </div>
            <div class="col-12">
              <button type="button" class="btn btn-success" onclick="addToList()">
                <i class="bi bi-plus-circle me-1"></i>Adicionar à Lista
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Lista de Distribuição</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped mb-0">
            <thead class="table-dark">
              <tr>
                <th>Medicamento/Produto</th>
                <th>Apresentação</th>
                <th>Marca</th>
                <th>Lote</th>
                <th>Validade</th>
                <th>Quantidade</th>
                <th>Categoria</th>
                <th>Estabelecimento</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody id="distribuicao-table-body"></tbody>
          </table>
        </div>
      </div>
    </div>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" onsubmit="return confirmDistribuicao();">
      <input type="hidden" name="action" value="confirm">
      <input type="hidden" id="distribuicoes-input" name="distribuicoes">
      <button type="submit" class="btn btn-primary btn-lg">
        <i class="bi bi-check-circle me-1"></i>Confirmar Distribuição
      </button>
    </form>
  </div>
</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
