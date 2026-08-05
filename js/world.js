window.addEventListener("load", () => {

    const loader = document.getElementById("loading-screen");

    setTimeout(() => {

        loader.style.opacity = "0";
        loader.style.pointerEvents = "none";

        setTimeout(() => {

            loader.remove();

        },800);

    },900);

});

document.addEventListener("mousemove",(e)=>{

    document.documentElement.style.setProperty(
        "--mouseX",
        e.clientX + "px"
    );

    document.documentElement.style.setProperty(
        "--mouseY",
        e.clientY + "px"
    );

});
