document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('archCanvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Configuración de estilos de línea
    ctx.lineWidth = 2;
    ctx.strokeStyle = '#555';
    ctx.lineCap = 'round';

    // Coordenadas base (deben coincidir con los divs en HTML)
    const client = { x: 400, y: 40 };
    const gateway = { x: 400, y: 120 };
    
    const servicesY = 270;
    const servicesX = [100, 250, 400, 550, 700]; // Auth, Branch, Inv, Sales, Others

    const dbMasterY = 400;
    const dbReplicaY = 460;

    // Función para dibujar flecha
    function drawArrow(fromX, fromY, toX, toY) {
        const headlen = 10; // longitud de la cabeza
        const dx = toX - fromX;
        const dy = toY - fromY;
        const angle = Math.atan2(dy, dx);
        
        ctx.beginPath();
        ctx.moveTo(fromX, fromY);
        ctx.lineTo(toX, toY);
        ctx.stroke();
        
        ctx.beginPath();
        ctx.moveTo(toX, toY);
        ctx.lineTo(toX - headlen * Math.cos(angle - Math.PI / 6), toY - headlen * Math.sin(angle - Math.PI / 6));
        ctx.lineTo(toX - headlen * Math.cos(angle + Math.PI / 6), toY - headlen * Math.sin(angle + Math.PI / 6));
        ctx.fill();
    }

    // 1. Cliente -> Gateway
    drawArrow(client.x, client.y + 20, gateway.x, gateway.y - 10);

    // 2. Gateway -> Servicios
    servicesX.forEach(x => {
        drawArrow(gateway.x, gateway.y + 20, x + 50, servicesY - 10);
        
        // 3. Servicios -> DB Master
        // Simplificación visual: líneas verticales hacia abajo
        ctx.setLineDash([5, 5]); // Línea punteada para conexión a DB
        ctx.beginPath();
        ctx.moveTo(x + 50, servicesY + 20);
        ctx.lineTo(x + 50, dbMasterY - 10);
        ctx.stroke();
    });

    // 4. DB Master <-> DB Replica (Replicación)
    ctx.setLineDash([]); // Restaurar línea sólida
    ctx.strokeStyle = '#e74c3c'; // Color rojo para replicación
    
    // Dibujar línea horizontal de replicación
    ctx.beginPath();
    ctx.moveTo(50, dbMasterY + 15);
    ctx.lineTo(750, dbMasterY + 15);
    ctx.stroke();

    // Flechas de replicación hacia abajo
    servicesX.forEach(x => {
        drawArrow(x + 50, dbMasterY + 15, x + 50, dbReplicaY - 10);
    });

    // Leyenda simple en el canvas
    ctx.fillStyle = '#333';
    ctx.font = '12px Arial';
    ctx.fillText("Flujo de Petición (HTTP)", 600, 50);
    
    ctx.strokeStyle = '#555';
    ctx.beginPath();
    ctx.moveTo(580, 45);
    ctx.lineTo(595, 45);
    ctx.stroke();

    ctx.fillStyle = '#e74c3c';
    ctx.fillText("Replicación (Binlog)", 600, 70);
    
    ctx.strokeStyle = '#e74c3c';
    ctx.beginPath();
    ctx.moveTo(580, 65);
    ctx.lineTo(595, 65);
    ctx.stroke();
});
