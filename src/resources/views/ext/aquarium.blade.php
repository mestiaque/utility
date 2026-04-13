<div id="aquarium"></div>
<style>
#aquarium{
    position:fixed;
    inset:0;
    z-index:9999;
    pointer-events:none;
}

#aquarium canvas{
    position:absolute;
    inset:0;
}

.aquarium-fish{
    position:absolute;
    width:90px;
    pointer-events:auto;
    user-select:none;
    transform-origin:center;
}

.aquarium-fish.flip{
    transform:scaleX(-1);
}
</style>
<script>
(function(){

const container = document.getElementById("aquarium");
if(!container) return;

// canvas
const canvas = document.createElement("canvas");
container.appendChild(canvas);

const ctx = canvas.getContext("2d");

function resize(){
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}
resize();
window.addEventListener("resize",resize);

// mouse
let mouse={x:-1000,y:-1000};

window.addEventListener("mousemove",e=>{
    mouse.x=e.clientX;
    mouse.y=e.clientY;
});

// fish
const fish=document.createElement("img");
fish.src="/vendor/mestiaque/utility/fish.gif";
fish.className="aquarium-fish";

container.appendChild(fish);

let x=window.innerWidth/2;
let y=window.innerHeight/2;

let angle=Math.random()*Math.PI*2;
let speed=1.5;
let boost=0;

const fishWidth=90;
const fishHeight=50;

fish.addEventListener("click",()=>{
    boost=5;
});

// bubbles
const bubbles=[];

function spawnBubble(x,y){

    bubbles.push({
        x:x,
        y:y,
        r:Math.random()*3+2,
        speed:Math.random()*1+0.5
    });

    if(bubbles.length>120) bubbles.shift();
}

// movement
function updateFish(){

    const dx=x-mouse.x;
    const dy=y-mouse.y;
    const dist=Math.sqrt(dx*dx+dy*dy);

    // mouse fear
    if(dist<140){
        angle=Math.atan2(dy,dx);
        boost=4;
    }

    // random turning
    angle += (Math.random()-0.5)*0.15;

    let currentSpeed=speed+boost;

    x+=Math.cos(angle)*currentSpeed;
    y+=Math.sin(angle)*currentSpeed;

    // screen bounds
    if(x<0){
        x=0;
        angle=Math.PI-angle;
    }

    if(x>window.innerWidth-fishWidth){
        x=window.innerWidth-fishWidth;
        angle=Math.PI-angle;
    }

    if(y<0){
        y=0;
        angle=-angle;
    }

    if(y>window.innerHeight-fishHeight){
        y=window.innerHeight-fishHeight;
        angle=-angle;
    }

    fish.style.left=x+"px";
    fish.style.top=y+"px";

    // flip direction
    if(Math.cos(angle)>0){
        fish.classList.add("flip");
    }else{
       fish.classList.remove("flip");
    }

    // mouth position
    let mouthX=x + (Math.cos(angle)<0 ? fishWidth : 0);
    let mouthY=y + fishHeight/2;

    // spawn bubbles from mouth
    if(Math.random()<0.15){
        spawnBubble(mouthX,mouthY);
    }

    if(boost>0) boost*=0.9;
}

// draw bubbles
function drawBubbles(){

    ctx.clearRect(0,0,canvas.width,canvas.height);

    ctx.strokeStyle="rgba(255,255,255,0.7)";
    ctx.lineWidth=1;

    bubbles.forEach(b=>{

        b.y -= b.speed;

        ctx.beginPath();
        ctx.arc(b.x,b.y,b.r,0,Math.PI*2);
        ctx.stroke();
    });
}

// animation
function animate(){

    updateFish();
    drawBubbles();

    requestAnimationFrame(animate);
}

animate();

})();
</script>
