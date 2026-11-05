<?php
// Asistente de la tienda. Solo aparece si GEMINI_API_KEY está configurada y necesita JavaScript.
if ((string) config('app.chat.api_key', '') === '') {
    return;
}
?>
<div class="chat" data-chat data-chat-url="<?= e(url('/asistente/mensaje')) ?>" hidden>
    <section class="chat-panel" id="chat-panel" role="dialog" aria-labelledby="chat-title" hidden>
        <header class="chat-head">
            <p class="chat-title" id="chat-title"><?= icon('paw-print', 'icon icon-sm') ?> Pati, asistente de Patita</p>
            <button type="button" class="chat-icon-btn" data-chat-reset title="Nueva conversación">Reiniciar</button>
            <button type="button" class="chat-icon-btn" data-chat-close aria-label="Cerrar chat"><?= icon('x', 'icon icon-sm') ?></button>
        </header>
        <div class="chat-log" data-chat-log aria-live="polite">
            <p class="chat-msg chat-msg-bot">¡Hola! Soy Pati. Pregúntame por productos, cuidados de tu mascota, envíos o pagos.</p>
        </div>
        <p class="chat-note">Respuestas generadas por IA (Gemini). Pueden equivocarse y no sustituyen a un veterinario.</p>
        <form class="chat-form" data-chat-form>
            <label class="sr-only" for="chat-input">Escribe tu pregunta</label>
            <input id="chat-input" name="message" type="text" maxlength="500" autocomplete="off" placeholder="Escribe tu pregunta…" required>
            <button type="submit" class="chat-send" aria-label="Enviar"><?= icon('chevron-right') ?></button>
        </form>
    </section>
    <button type="button" class="chat-fab" data-chat-toggle aria-expanded="false" aria-controls="chat-panel">
        <?= icon('messages-square') ?><span class="sr-only">Abrir chat con el asistente</span>
    </button>
</div>
