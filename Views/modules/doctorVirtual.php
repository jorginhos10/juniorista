<?php

ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
    header("location: login");
} else {
    require "header.php";
    require "sidebar.php";

    if ($_SESSION['ventas'] == 1) {
?>
<div class="container">
<div class="main-content">
<div id="chat">
  <h2>🐾 VetGPT - Asistente Veterinario</h2>
  <div id="messages"></div>
  <input type="text" id="userInput" placeholder="Ej: ¿Qué hago si mi gato estornuda mucho?" />
  <button onclick="sendMessage()">Enviar</button>
</div>
</div>
</div>
<style>
  body {
    font-family: sans-serif; background: #f2f2f2;
    display: flex; justify-content: center; padding: 30px;
  }

  #chat {
    width: 100%;
    max-width: 750px;
    min-width: 600px;
    min-height: 500px;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 0 12px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
  }

  #messages {
    flex-grow: 1;
    overflow-y: auto;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 10px;
    background: #fefefe;
    max-height: 500px;
  }

  .message {
    margin: 10px 0;
    padding: 10px 15px;
    border-radius: 10px;
    white-space: pre-wrap;
  }

  .user { background: #d0e8ff; text-align: right; }
  .bot  { background: #e0ffe1; text-align: left; }

  input, button {
    width: 100%;
    padding: 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
    margin-top: 5px;
  }

  button {
    background-color: #28a745;
    color: white;
    font-weight: bold;
    border: none;
    cursor: pointer;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
  }

  th, td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: left;
  }

  th {
    background-color: #f0f0f0;
  }
</style>

<script>
  const apiKey = "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";
  const apiUrl = "https://openrouter.ai/api/v1/chat/completions";

  const soundWaiting = new Audio("https://cdn.pixabay.com/download/audio/2022/03/15/audio_24fdc1e2d3.mp3");
  const soundReply   = new Audio("https://cdn.pixabay.com/download/audio/2022/03/01/audio_f2a1c97012.mp3");

  async function sendMessage() {
    const input = document.getElementById('userInput');
    const message = input.value.trim();
    if (!message) return;

    appendMessage('user', message);
    input.value = '';
    appendMessage('bot', '⏳ Pensando...');
    scrollChatToBottom();
    soundWaiting.play();

    try {
      // Si preguntan por el creador, responder manualmente
      const lowerMessage = message.toLowerCase();
      if (lowerMessage.includes("quién te creó") || lowerMessage.includes("quien te hizo") || lowerMessage.includes("creador")) {
        document.querySelector('#messages .bot:last-child').remove();
        appendMessage('bot', "<b>Fui creado, diseñado y programado por Jorge Albeiro Valencia Bolívar.</b>");
        soundReply.play();
        return;
      }

      const response = await fetch(apiUrl, {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${apiKey}`,
          "Content-Type": "application/json",
          "HTTP-Referer": "http://localhost",
          "X-Title": "VetGPT"
        },
        body: JSON.stringify({
          model: "mistralai/mistral-small-3.2-24b-instruct:free",
          messages: [
            {
              role: "system",
              content: "Eres un asistente de inteligencia artificial operado por un veterinario profesional. Tu tarea es actuar como un veterinario clínico. Cuando se describen síntomas o condiciones de un animal, debes sugerir posibles enfermedades comunes (en especial virales), describirlas brevemente y proporcionar tratamientos simulados como si tú mismo recetaras. Incluye nombres de medicamentos (antibióticos, antipiréticos, antivirales, etc.), dosis aproximadas (según peso si es necesario), y cuidados necesarios. Nunca digas 'llevar al veterinario', porque ya lo eres. Responde en HTML bien estructurado: usa <b> para títulos, <br> para separar líneas, y <table> si es útil. Sé breve, claro y preciso. Responde en español."
            },
            { role: "user", content: message }
          ]
        })
      });

      const data = await response.json();
      document.querySelector('#messages .bot:last-child').remove();
      const reply = data.choices?.[0]?.message?.content || '❌ Error al responder.';
      appendMessage('bot', reply);
      soundReply.play();
    } catch (error) {
      document.querySelector('#messages .bot:last-child').remove();
      appendMessage('bot', '⚠️ Error al conectar con la IA.');
      console.error(error);
    }
  }

  function appendMessage(sender, text) {
    const div = document.createElement('div');
    div.className = `message ${sender}`;
    div.innerHTML = text;
    document.getElementById('messages').appendChild(div);
    scrollChatToBottom();
  }

  function scrollChatToBottom() {
    const container = document.getElementById('messages');
    container.scrollTop = container.scrollHeight;
  }

  // Mensaje de bienvenida
  window.onload = () => {
    const bienvenida = `
<b>¡Hola! Soy tu asistente veterinario virtual</b><br><br>
Mi tarea es proporcionarte información sobre posibles enfermedades,<br>
tratamientos y cuidados necesarios.<br><br>
<b>Describe aquí una afección o síntoma</b>`;
    appendMessage('bot', bienvenida);
  };
</script>
<?php
    } else {
        require "access.php";
    }
    require "footer.php";
    ?>
<?php
}
ob_end_flush();
?>
