document.addEventListener('DOMContentLoaded', () => {
    const slides = document.querySelectorAll('.slide');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const progressBar = document.getElementById('progress');
    let currentSlide = 0;

    function updateSlides() {
        slides.forEach((slide, index) => {
            slide.classList.remove('active', 'prev');
            if (index === currentSlide) {
                slide.classList.add('active');
            } else if (index < currentSlide) {
                slide.classList.add('prev');
            }
        });

        // Update progress bar
        const progress = ((currentSlide + 1) / slides.length) * 100;
        progressBar.style.width = `${progress}%`;

        // Check if current slide is the diagram slide (id="slide-1-1")
        const currentSlideElement = slides[currentSlide];
        if (currentSlideElement.id === 'slide-1-1') {
            // Small delay to allow transition to finish or start
            setTimeout(drawArchitecture, 300);
        }
    }

    function nextSlide() {
        if (currentSlide < slides.length - 1) {
            currentSlide++;
            updateSlides();
        }
    }

    function prevSlide() {
        if (currentSlide > 0) {
            currentSlide--;
            updateSlides();
        }
    }

    nextBtn.addEventListener('click', nextSlide);
    prevBtn.addEventListener('click', prevSlide);

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowRight' || e.key === ' ') nextSlide();
        if (e.key === 'ArrowLeft') prevSlide();
    });

    // Canvas Drawing Logic
    function drawArchitecture() {
        const canvas = document.getElementById('archCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Styles
        ctx.strokeStyle = '#3b82f6'; // Primary color
        ctx.lineWidth = 2;
        ctx.font = 'bold 12px Inter';
        ctx.textAlign = 'center';

        // Helper to draw box
        function drawBox(x, y, w, h, text, bgColor = '#1e293b', borderColor = '#3b82f6') {
            ctx.fillStyle = bgColor;
            ctx.beginPath();
            ctx.roundRect(x, y, w, h, 8);
            ctx.fill();
            ctx.strokeStyle = borderColor;
            ctx.stroke();
            
            ctx.fillStyle = '#fff';
            ctx.fillText(text, x + w/2, y + h/2 + 4);
        }

        // Helper to draw arrow
        function drawArrow(fromX, fromY, toX, toY) {
            ctx.beginPath();
            ctx.moveTo(fromX, fromY);
            ctx.lineTo(toX, toY);
            ctx.strokeStyle = '#64748b';
            ctx.stroke();
            
            // Arrowhead
            const angle = Math.atan2(toY - fromY, toX - fromX);
            ctx.beginPath();
            ctx.moveTo(toX, toY);
            ctx.lineTo(toX - 10 * Math.cos(angle - Math.PI / 6), toY - 10 * Math.sin(angle - Math.PI / 6));
            ctx.lineTo(toX - 10 * Math.cos(angle + Math.PI / 6), toY - 10 * Math.sin(angle + Math.PI / 6));
            ctx.fillStyle = '#64748b';
            ctx.fill();
        }

        // Draw Nodes
        // Client
        drawBox(200, 20, 100, 40, "Cliente", '#0f172a', '#fff');
        
        // Gateway
        drawBox(200, 100, 100, 40, "API Gateway", '#8b5cf6', '#fff');
        drawArrow(250, 60, 250, 100);

        // Services Row
        const services = ['Auth', 'Branch', 'Inv', 'Sales'];
        const startX = 50;
        const gap = 110;
        
        services.forEach((svc, i) => {
            const x = startX + (i * gap);
            drawBox(x, 200, 90, 40, svc, '#1e293b', '#10b981');
            // Arrow from Gateway
            drawArrow(250, 140, x + 45, 200);
        });

        // DB Row
        drawBox(200, 280, 100, 40, "DB Master", '#ef4444', '#fff');
        
        // DB Arrows
        services.forEach((svc, i) => {
            const x = startX + (i * gap);
            drawArrow(x + 45, 240, 250, 280);
        });
    }

    // Initialize
    updateSlides();
});