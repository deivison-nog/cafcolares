<?php
include 'db.php';

date_default_timezone_set('America/Sao_Paulo');

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

            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
            $stmt->execute([$_SESSION['usuario']]);
            $estabelecimento_id = $stmt->fetchColumn();

            if (!$estabelecimento_id) {
                echo json_encode(["erro" => "Estabelecimento não encontrado para o usuário autenticado."]);
                exit;
            }

            try {
                $stmt = $pdo->prepare('SELECT id FROM pacientes WHERE nome = ?');
                $stmt->execute([$paciente]);
                $paciente_id = $stmt->fetchColumn();

                if (!$paciente_id) {
                    $data_nascimento = !empty($item['data_nascimento']) ? $item['data_nascimento'] : null;
                    $endereco = !empty($item['endereco']) ? $item['endereco'] : null;

                    $stmt = $pdo->prepare('INSERT INTO pacientes (nome, data_nascimento, endereco) VALUES (?, ?, ?)');
                    $stmt->execute([$paciente, $data_nascimento, $endereco]);
                    $paciente_id = $pdo->lastInsertId();
                }

                $stmt = $pdo->prepare('INSERT INTO distribuicao_pacientes (medicamento_id, paciente_id, quantidade, data_distribuicao, hora_distribuicao, estabelecimento_id) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$medicamento_id, $paciente_id, $quantidade, $data_distribuicao, $hora_distribuicao, $estabelecimento_id]);

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
  <title>Saída de Medicamentos – CAF</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/style.css">
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

      fetch('verificar_estoque.php?id=' + medicamento_id)
        .then(response => response.json())
        .then(data => {
          if (data.quantidade < quantidade) {
            alert('Quantidade em estoque insuficiente.');
          } else {
            const item = { medicamento_id, medicamento, quantidade, paciente, data_nascimento, endereco };
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
        row.insertCell(0).textContent = item.medicamento;
        row.insertCell(1).textContent = item.quantidade;
        row.insertCell(2).textContent = item.paciente;
        const btnCell = row.insertCell(3);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-danger';
        const icon = document.createElement('i');
        icon.className = 'bi bi-trash';
        btn.appendChild(icon);
        btn.onclick = function() { removerItem(index); };
        btnCell.appendChild(btn);
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

<div id="wrapper" class="d-flex">
  <?php include 'includes/menu_lateral.php'; ?>
  <div class="main-content flex-grow-1">
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-box-arrow-right fs-3 me-2 text-danger"></i>
      <h2 class="mb-0">Saída de Medicamentos</h2>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-search me-2"></i>Adicionar Item</h5>
      </div>
      <div class="card-body">
        <form id="form-saida" method="post">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="medicamento" class="form-label">Medicamento/Produto <span class="text-danger">*</span></label>
              <div class="position-relative">
                <input type="text" class="form-control" id="medicamento" name="medicamento"
                  onkeyup="fetchSuggestions(this.value, 'medicamento')" autocomplete="off" required>
                <div id="medicamento-suggestion-box" class="suggestion-box"></div>
                <input type="hidden" id="medicamento-id" name="medicamento_id">
              </div>
            </div>
            <div class="col-md-6">
              <label for="paciente" class="form-label">Paciente <span class="text-danger">*</span></label>
              <div class="position-relative">
                <input type="text" class="form-control" id="paciente" name="paciente"
                  onkeyup="fetchSuggestions(this.value, 'paciente')" autocomplete="off" required>
                <div id="paciente-suggestion-box" class="suggestion-box"></div>
                <input type="hidden" id="paciente-id" name="paciente_id">
              </div>
            </div>
            <div class="col-md-4">
              <label for="data_nascimento" class="form-label">Data de Nascimento</label>
              <input type="date" class="form-control" id="data_nascimento" name="data_nascimento">
            </div>
            <div class="col-md-4">
              <label for="endereco" class="form-label">Endereço</label>
              <input type="text" class="form-control" id="endereco" name="endereco">
            </div>
            <div class="col-md-4">
              <label for="quantidade" class="form-label">Quantidade <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="quantidade" name="quantidade" min="1" required>
            </div>
            <div class="col-12">
              <button type="button" class="btn btn-success" onclick="incluirNaLista()">
                <i class="bi bi-plus-circle me-1"></i>Incluir na Lista
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Itens na Lista</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped mb-0">
            <thead class="table-dark">
              <tr>
                <th>Medicamento</th>
                <th>Quantidade</th>
                <th>Paciente</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody id="tabela-itens"></tbody>
          </table>
        </div>
      </div>
    </div>

    <button type="button" class="btn btn-primary btn-lg" onclick="registrarSaida()">
      <i class="bi bi-check-circle me-1"></i>Registrar Saída
    </button>
  </div>
</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
