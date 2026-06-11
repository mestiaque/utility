<html>

<style>
    //Colors
$bg: #000;

$tab-bg: #f9f9f9;

$f-color: #ba9872;
$s-color: #967b5c;
$t-color: #a38564;
$fo-color: #f9f9f9;
$fi-color: #e2e2e2;

//Mixins
@mixin posAbsolute($p) {
  width: 100%;
  height: 100%;
  bottom: 0;
  left: 0;
  @if ($p == a) {
    position: absolute;
  } @else if($p == r) {
    position: relative;
  }
}

// General styles
* {
  padding: 0;
  margin: 0;
  box-sizing: border-box;
}

html,
body {
  height: 100%;
}

body {
  width: 100%;
  background-color: $bg;
  font-size: 20px;
  display: flex;
  justify-content: center;
  align-items: center;

  @media (max-height: 500px) {
    margin: 25% 0 25% 0;
  }
}

//Styles
.envelop {
  width: 15rem;
  height: 10rem;
  position: absolute;
  left: 0;
  right: 0;
  margin: auto;

  @media (min-width: 400px) and (max-width: 600px) {
    width: 20rem;
    height: 15rem;
  }
  @media (min-width: 600px) {
    width: 25rem;
    height: 20rem;
  }
  @media (min-width: 600px) and (min-height: 600px) {
    bottom: 20%;
  }

  &__front-paper {
    @include posAbsolute(a);
    border-radius: 0.7rem;
    border: 0.35rem solid $s-color;
    background-color: $f-color;
    clip-path: polygon(100% 0%, 50% 70%, 0% 0%, 0% 100%, 100% 100%);
    z-index: 300;
  }

  &__back-paper {
    @include posAbsolute(a);
    border-radius: 0.7rem;
    background-color: $s-color;
    clip-path: polygon(0% 0%, 100% 0%, 90% 100%, 0% 90%);
    z-index: 100;
  }

  &__up-paper {
    @include posAbsolute(r);
    border-radius: 0.7rem;
    background-color: $s-color;
    z-index: 400;
    clip-path: polygon(0% 0%, 100% 0%, 50% 81%);
  }

  &__sticker {
    width: 100%;
    height: 20%;
    position: absolute;
    margin: auto;
    top: 30%;
    left: 0;
    bottom: 0;
    right: 0;
    z-index: 400;
    background-image: url("https://www.freepnglogos.com/uploads/heart-png/emoji-heart-33.png");
    background-color: $fo-color;
    border: 0.3rem solid $fi-color;
    background-size: 2rem;
    background-position: center;

    @media (max-width: 400px) {
      background-size: 1.2rem;
    }
    @media (min-width: 600px) {
      height: 15%;
    }
  }

  &__false-sticker {
    width: 20%;
    height: 20%;
    position: absolute;
    margin: auto;
    top: 30%;
    left: 80%;
    bottom: 0;
    right: 0;
    z-index: 300;
    background-image: url("https://www.freepnglogos.com/uploads/heart-png/emoji-heart-33.png");
    background-color: $fo-color;
    border: 0.3rem solid $fi-color;
    background-size: 2rem;
    background-position: center;

    @media (max-width: 400px) {
      background-size: 1.2rem;
    }
    @media (min-width: 600px) {
      height: 15%;
    }
  }

  &__content {
    @include posAbsolute(a);
    z-index: 200;
  }
}

.love-notes {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.note {
  width: 95%;
  height: 30%;
  background-color: $tab-bg;
  position: absolute;
  overflow: hidden;
  transition: height 0.5s, opacity 0.25s;
  box-shadow: 0px 0px 5px 0px rgba(0, 0, 0, 0.75);

  &:nth-child(1) {
    bottom: 60%;
  }

  &:nth-child(2) {
    bottom: 40%;
  }

  &:nth-child(3) {
    bottom: 20%;
  }

  &:hover {
    cursor: pointer;
    height: 45%;
  }

  &__text {
    font-family: "Sofia";
    padding: 1rem;

    p {
      font-size: 0.9rem;
      margin-bottom: 1rem;
      text-align: center;

      @media (min-width: 300px) and (max-width: 600px) {
        font-size: 1.025rem;
      }
      @media (min-width: 600px) {
        font-size: 1.15rem;
      }
    }
  }
}

.scissors {
  cursor: url("https://i.postimg.cc/GtLCdKxR/sisors.png"), pointer;
  &:active {
    cursor: url("https://i.postimg.cc/GtXQ7WPZ/pngwing-com.png"), pointer;
  }
}

.cursor {
  cursor: pointer;
}
</style>

<body class="scissors">
  <div class="envelop">
    <div class="envelop__front-paper"></div>
    <div class="envelop__back-paper"></div>
    <div class="envelop__up-paper js-up-paper"></div>
    <div class="envelop__sticker js-sticker"></div>
    <div class="envelop__false-sticker"></div>
    <div class="envelop__content js-envelop-content">
      <div class="love-notes">
        <div class="note js-note">
          <div class="note__text">
            <p>
              Oii minha princesa, aqui estamos completando mais um mês juntos, é sempre uma alegria poder acordar e saber que tenho você ao meu lado e sentir que isso será para sempre é magnífico
            </p>
          </div>
        </div>
        <div class="note js-note">
          <div class="note__text">
            <p>quero que eles nunca acabem e que tenham sempre muitos outros pela frente.</p>
          </div>
        </div>
        <div class="note js-note">
          <div class="note__text">
            <p>você é a melhor coisa que me aconteceu, obrigado por me escolher todos os dias.</p>
            <p>Te amo muito! &hearts;.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    //Variables
let mobile_media_query = window.matchMedia("(max-width: 400px)");
let tablet_media_query = window.matchMedia(
  "(min-width: 400px) and (max-width: 600px)"
);
const notes = document.querySelectorAll(".js-note");

//-> Function that resets the size of the notes.
function recize_notes() {
  for (let i = 0; i < notes.length; i++) {
    if (notes[i].classList.contains("active")) {
      notes[i].classList.remove("active");
      gsap.set(notes[i], {
        height: "30%",
        clearProps: "all"
      });
    }
  }
}

//-> Main function that enables all the notes.
function notes_ready() {
  gsap.to(".js-envelop-content", {
    height: "110%",
    duration: 0.5
  });

  for (let i = 0; i < notes.length; i++) {
    notes[i].addEventListener("click", function () {
      if (mobile_media_query.matches) {
        if (this.classList.contains("active")) {
          this.classList.remove("active");
          gsap.set(this, {
            height: "30%",
            clearProps: "all"
          });
        } else {
          for (let i = 0; i < notes.length; i++) {
            if (notes[i].classList.contains("active")) {
              notes[i].classList.remove("active");
              gsap.set(notes[i], {
                height: "30%",
                clearProps: "all"
              });
            }
          }
          this.classList.add("active");
          gsap.set(this, {
            height: 125 + 40 * i + "%"
          });
        }
      } else if (tablet_media_query.matches) {
        if (this.classList.contains("active")) {
          this.classList.remove("active");
          gsap.set(this, {
            height: "30%",
            clearProps: "all"
          });
        } else {
          for (let i = 0; i < notes.length; i++) {
            if (notes[i].classList.contains("active")) {
              notes[i].classList.remove("active");
              gsap.set(notes[i], {
                height: "30%",
                clearProps: "all"
              });
            }
          }
          this.classList.add("active");
          gsap.set(this, {
            height: 80 + 21 * i + "%"
          });
        }
      } else {
        if (this.classList.contains("active")) {
          this.classList.remove("active");
          gsap.set(this, {
            height: "30%",
            clearProps: "all"
          });
        } else {
          for (let i = 0; i < notes.length; i++) {
            if (notes[i].classList.contains("active")) {
              notes[i].classList.remove("active");
              gsap.set(notes[i], {
                height: "30%",
                clearProps: "all"
              });
            }
          }
          this.classList.add("active");
          gsap.set(this, {
            height: 70 + 20 * i + "%"
          });
        }
      }
    });
  }
}

//-> Function that set up the up paper of the envelope.
function set_up_paper() {
  var arr = [0, 0, 100, 0, 50, 61];
  gsap.set(".js-up-paper", {
    bottom: "97%",
    rotation: 180,
    zIndex: 200,
    clipPath:
      "polygon(" +
      arr[0] +
      "%" +
      arr[1] +
      "%," +
      arr[2] +
      "%" +
      arr[3] +
      "%," +
      arr[4] +
      "%" +
      arr[5] +
      "%)",
    onComplete: notes_ready
  });
}

//-> Function that starts the up paper transition.
function envelop_transition() {
  gsap.to(".js-up-paper", {
    bottom: "1%",
    duration: 0.25,
    onComplete: set_up_paper
  });
  document
    .querySelector(".js-up-paper")
    .removeEventListener("click", envelop_transition);
  document.querySelector(".js-up-paper").classList.remove("cursor");
}

//-> Function that allows cut the sticker.
function sticker() {
  gsap.set(".js-sticker", { width: "20%", left: "-80%" });
  document.body.classList.remove("scissors");
  document.querySelector(".js-sticker").removeEventListener("click", sticker);
  document
    .querySelector(".js-up-paper")
    .addEventListener("click", envelop_transition);
  document.querySelector(".js-up-paper").classList.add("cursor");
}

document.querySelector(".js-sticker").addEventListener("click", sticker);

window.onresize = function (event) {
  recize_notes();
};
    </script>

</body>

</html>