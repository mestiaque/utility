<style>
.swimming-fish {
    position: fixed;
    width: 80px;
    height: auto;
    z-index: 9999;
    pointer-events: none;
    transition: transform 0.1s linear;
}

.swimming-fish.flip {
    transform: scaleX(-1);
}
</style>

<img src="{{ asset('vendor/mestiaque/utility/fish.gif') }}" 
     alt="Swimming Fish" 
     class="swimming-fish" 
     id="swimmingFish">

<script>
(function() {
    const fish = document.getElementById('swimmingFish');
    if (!fish) return;
    
    // Initial position
    let posX = Math.random() * (window.innerWidth - 100);
    let posY = Math.random() * (window.innerHeight - 100);
    
    // Velocity
    let velX = (Math.random() - 0.5) * 4 + 2; // Random speed between 2 and 4
    let velY = (Math.random() - 0.5) * 4 + 2;
    
    // Minimum speed
    if (Math.abs(velX) < 1) velX = velX < 0 ? -2 : 2;
    if (Math.abs(velY) < 1) velY = velY < 0 ? -2 : 2;
    
    const fishWidth = 80;
    const fishHeight = 50;
    
    function updatePosition() {
        posX += velX;
        posY += velY;
        
        // Bounce off left/right edges
        if (posX <= 0) {
            posX = 0;
            velX = Math.abs(velX);
            fish.classList.remove('flip');
        } else if (posX >= window.innerWidth - fishWidth) {
            posX = window.innerWidth - fishWidth;
            velX = -Math.abs(velX);
            fish.classList.add('flip');
        }
        
        // Bounce off top/bottom edges
        if (posY <= 0) {
            posY = 0;
            velY = Math.abs(velY);
        } else if (posY >= window.innerHeight - fishHeight) {
            posY = window.innerHeight - fishHeight;
            velY = -Math.abs(velY);
        }
        
        // Flip fish based on direction
        if (velX < 0) {
            fish.classList.add('flip');
        } else {
            fish.classList.remove('flip');
        }
        
        fish.style.left = posX + 'px';
        fish.style.top = posY + 'px';
        
        requestAnimationFrame(updatePosition);
    }
    
    // Set initial position
    fish.style.left = posX + 'px';
    fish.style.top = posY + 'px';
    
    // Start animation
    requestAnimationFrame(updatePosition);
})();
</script>
