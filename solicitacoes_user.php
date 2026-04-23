<?php
session_start();

date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    echo "Usuário não encontrado no banco de dados.";
    exit;
}

include 'db.php';

$usuario_id = $_SESSION['usuario_id'];

$sql_check_user = 'SELECT id FROM usuarios WHERE id = ?';
$stmt_check_user = $pdo->prepare($sql_check_user);
$stmt_check_user->execute([$usuario_id]);

if ($stmt_check_user->rowCount() == 0) {
    echo "Usuário não encontrado no banco de dados.";
    exit;
}

$sql = 'SELECT * FROM medicamentos WHERE estabelecimento = ?';
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_id]);
$medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$medicamentos_json = json_encode($medicamentos);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitações de Medicamentos – CAF</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'includes/head.php'; ?>
<div id="wrapper" class="d-flex">
  <?php include 'includes/menu_lateral.php'; ?>
  <div class="main-content flex-grow-1">
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-send fs-3 me-2 text-primary"></i>
      <h2 class="mb-0">Solicitações de Medicamentos</h2>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-search me-2"></i>Adicionar Medicamento</h5>
      </div>
      <div class="card-body">
        <form id="medicamento-form" onsubmit="event.preventDefault(); addToList();" class="row g-3">
          <div class="col-md-6">
            <label for="pesquisa" class="form-label">Buscar Medicamento</label>
            <div class="position-relative">
              <input type="text" class="form-control" id="pesquisa" name="pesquisa"
                autocomplete="off" onkeyup="fetchMedicamentos(this.value)" placeholder="Digite o nome do medicamento...">
              <div id="suggestions" class="suggestion-box" style="display:none;"></div>
            </div>
          </div>
          <div class="col-md-4">
            <label for="quantidade" class="form-label">Quantidade</label>
            <input type="number" class="form-control" id="quantidade" name="quantidade" min="1" required>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-success w-100">
              <i class="bi bi-plus-circle me-1"></i>Adicionar
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Medicamentos Selecionados</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped mb-0">
            <thead class="table-dark">
              <tr>
                <th>Medicamento</th>
                <th>Quantidade Solicitada</th>
                <th>Ação</th>
              </tr>
            </thead>
            <tbody id="lista-medicamentos"></tbody>
          </table>
        </div>
      </div>
    </div>

    <button id="enviar-solicitacao" class="btn btn-primary btn-lg" onclick="enviarSolicitacao()">
      <i class="bi bi-send me-1"></i>Enviar Solicitação
    </button>
  </div>
</div>
<?php include 'includes/foot.php'; ?>

<script>
  const medicamentosEstoque = <?php echo $medicamentos_json; ?>;

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

  function selectMedicamento(id, nome) {
    const pesquisa = document.getElementById('pesquisa');
    pesquisa.value = nome;
    pesquisa.setAttribute('data-id', id);
    document.getElementById('suggestions').style.display = 'none';
  }

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
    row.setAttribute('data-id', medicamentoId);

    const tdMed = document.createElement('td');
    tdMed.textContent = pesquisa.value;
    row.appendChild(tdMed);

    const tdQtd = document.createElement('td');
    tdQtd.textContent = quantidade;
    row.appendChild(tdQtd);

    const tdAcao = document.createElement('td');
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-sm btn-danger';
    const icon = document.createElement('i');
    icon.className = 'bi bi-trash';
    btn.appendChild(icon);
    btn.onclick = function() { removeRow(this); };
    tdAcao.appendChild(btn);
    row.appendChild(tdAcao);

    lista.appendChild(row);

    pesquisa.value = '';
    pesquisa.removeAttribute('data-id');
    document.getElementById('quantidade').value = '';
  }

  function removeRow(button) {
    button.closest('tr').remove();
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

    const usuario_id = <?php echo json_encode($_SESSION['usuario_id']); ?>;

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
          if (confirm('Deseja realmente confirmar a solicitação?')) {
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
