document.addEventListener("DOMContentLoaded", function () {
  const tabelaReservas = document.getElementById("tabela-reservas");
  if (tabelaReservas) {
    carregarReservas();
  }
});

function fazerReserva(event) {
  event.preventDefault();

  const nomeCliente = document.getElementById("nome_cliente").value;
  const telefoneCliente = document.getElementById("telefone_cliente").value;
  const numPessoas = document.getElementById("num_pessoas").value;
  const mesaId = document.getElementById("mesa_id").value;
  const dataReserva = document.getElementById("data_reserva").value;
  const horarioReserva = document.getElementById("horario_reserva").value;

  // Validação básica
  if (
    !nomeCliente ||
    !telefoneCliente ||
    !numPessoas ||
    !mesaId ||
    !dataReserva ||
    !horarioReserva
  ) {
    alert("Por favor, preencha todos os campos");
    return;
  }

  fetch("php/reserva.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    credentials: "include",
    body: `cliente_nome=${encodeURIComponent(
      nomeCliente
    )}&cliente_telefone=${encodeURIComponent(
      telefoneCliente
    )}&num_pessoas=${encodeURIComponent(
      numPessoas
    )}&mesa_id=${encodeURIComponent(mesaId)}&data_reserva=${encodeURIComponent(
      dataReserva
    )}&horario_reserva=${encodeURIComponent(horarioReserva)}`,
  })
    .then((response) => response.json())
    .then((data) => {
      alert(data.message);
      if (data.success) {
        document.getElementById("form-reserva").reset();
      }
    })
    .catch((error) => {
      console.error("Erro ao fazer reserva:", error);
    });
}

function carregarReservas() {
  const tbody = document.getElementById("tabela-reservas");
  if (!tbody) return;

  fetch("php/cancelar.php?acao=listar", {
    credentials: "include",
  })
    .then((response) => response.json())
    .then((data) => {
      tbody.innerHTML = "";

      data.forEach((reserva) => {
        const dataFormatada = new Date(reserva.data).toLocaleDateString(
          "pt-BR",
          { timeZone: "UTC" }
        );
        const hora = reserva.horario.substring(0, 5);

        const row = document.createElement("tr");
        row.innerHTML = `
          <td>${reserva.cliente_nome}</td>
          <td>${reserva.cliente_telefone}</td>
          <td>${reserva.mesa_numero}</td>
          <td>${dataFormatada}</td>
          <td>${hora}</td>
          <td>${reserva.num_pessoas}</td>
          <td>
            <button onclick="cancelarReserva(${reserva.reserva_id})" class="btn-cancelar">Cancelar</button>
            <button onclick="editarReserva(${reserva.reserva_id})" class="btn-editar">Editar</button>
          </td>
        `;
        tbody.appendChild(row);
      });
    })
    .catch((error) => {
      console.error("Erro ao carregar reservas:", error);
      alert("Erro ao carregar reservas");
    });
}

function editarReserva(reservaId) {
  fetch(`php/cancelar.php?acao=listar`, {
    credentials: "include",
  })
    .then((response) => response.json())
    .then((data) => {
      const reserva = data.find((res) => res.reserva_id == reservaId);
      if (reserva) {
        document.getElementById("reserva_id").value = reserva.reserva_id;
        document.getElementById("nome_cliente").value = reserva.cliente_nome;
        document.getElementById("telefone_cliente").value =
          reserva.cliente_telefone;
        document.getElementById("num_pessoas").value = reserva.num_pessoas;
        document.getElementById("mesa_id").value = reserva.mesa_numero;
        document.getElementById("data_reserva").value =
          reserva.data.split("T")[0];
        document.getElementById("horario_reserva").value =
          reserva.horario.substring(0, 5);

        document.getElementById("modal-editar").style.display = "block";
      }
    })
    .catch((error) => {
      console.error("Erro ao carregar dados da reserva:", error);
      alert("Erro ao carregar dados da reserva");
    });
}

function atualizarReserva() {
  const reservaId = document.getElementById("reserva_id").value;
  const nomeCliente = document.getElementById("nome_cliente").value;
  const telefoneCliente = document.getElementById("telefone_cliente").value;
  const numPessoas = document.getElementById("num_pessoas").value;
  const mesaId = document.getElementById("mesa_id").value;
  const dataReserva = document.getElementById("data_reserva").value;
  const horarioReserva = document.getElementById("horario_reserva").value;

  fetch("php/cancelar.php?acao=atualizar", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    credentials: "include",
    body: `reserva_id=${encodeURIComponent(
      reservaId
    )}&nome_cliente=${encodeURIComponent(
      nomeCliente
    )}&telefone_cliente=${encodeURIComponent(
      telefoneCliente
    )}&num_pessoas=${encodeURIComponent(
      numPessoas
    )}&mesa_id=${encodeURIComponent(mesaId)}&data_reserva=${encodeURIComponent(
      dataReserva
    )}&horario_reserva=${encodeURIComponent(horarioReserva)}`,
  })
    .then((response) => response.json())
    .then((data) => {
      alert(data.message);
      if (data.success) {
        carregarReservas();
        fecharModal();
      }
    })
    .catch((error) => {
      console.error("Erro ao atualizar reserva:", error);
      alert("Erro ao atualizar reserva");
    });
}

function cancelarReserva(reservaId) {
  if (confirm("Tem certeza que deseja cancelar esta reserva?")) {
    fetch("php/cancelar.php?acao=cancelar", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      credentials: "include",
      body: `reserva_id=${encodeURIComponent(reservaId)}`,
    })
      .then((response) => response.json())
      .then((data) => {
        alert(data.message);
        if (data.success) {
          carregarReservas();
        }
      })
      .catch((error) => {
        console.error("Erro ao cancelar reserva:", error);
        alert("Erro ao cancelar reserva");
      });
  }
}

function fecharModal() {
  const modalEditar = document.getElementById("modal-editar");
  if (modalEditar) {
    modalEditar.style.display = "none";
  }
}