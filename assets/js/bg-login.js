/**
 * KW24 - Background animado da tela de login
 * Rede com nós verdes (#26FF93) sobre gradiente cyan da marca
 */
(function() {
    const canvas = document.getElementById('kw24-bg-login');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    function resize() {
        canvas.width  = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    const nodes = [];
    for (let i = 0; i < 60; i++) {
        nodes.push({
            x:     Math.random() * canvas.width,
            y:     Math.random() * canvas.height,
            vx:    (Math.random() - 0.5) * 0.35,
            vy:    (Math.random() - 0.5) * 0.35,
            r:     Math.random() * 2.5 + 0.8,
            pulse: Math.random() * Math.PI * 2
        });
    }

    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const t = Date.now() / 1000;

        for (let i = 0; i < nodes.length; i++) {
            const n = nodes[i];
            n.x += n.vx; n.y += n.vy;
            if (n.x < 0 || n.x > canvas.width)  n.vx *= -1;
            if (n.y < 0 || n.y > canvas.height) n.vy *= -1;

            for (let j = i + 1; j < nodes.length; j++) {
                const m = nodes[j];
                const dx = m.x - n.x, dy = m.y - n.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < 130) {
                    ctx.beginPath();
                    ctx.strokeStyle = `rgba(255,255,255,${(1 - dist / 130) * 0.2})`;
                    ctx.lineWidth = 0.6;
                    ctx.moveTo(n.x, n.y); ctx.lineTo(m.x, m.y); ctx.stroke();
                }
            }

            const glow = 0.5 + 0.5 * Math.sin(t * 1.5 + n.pulse);
            ctx.beginPath();
            ctx.arc(n.x, n.y, n.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(38,255,147,${0.35 + glow * 0.4})`;
            ctx.fill();
        }

        requestAnimationFrame(draw);
    }

    draw();
})();
