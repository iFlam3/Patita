/*
 * Patita — arranque de animaciones. Se carga sin defer en el <head> para
 * ocultar los elementos animados antes del primer pintado (evita parpadeo).
 * Si el usuario pide movimiento reducido o el navegador no tiene
 * IntersectionObserver, no hace nada y todo se ve estático.
 * Si app.js no llega a iniciar las animaciones en 3 s, se quita la clase
 * para que nada quede oculto.
 */
(function () {
    'use strict';
    var root = document.documentElement;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || !('IntersectionObserver' in window)) {
        return;
    }
    root.classList.add('rv');
    window.setTimeout(function () {
        if (!window.patitaRevealReady) {
            root.classList.remove('rv');
        }
    }, 3000);
}());
