(function(){
  "use strict";
  function init(block){
    if(block.dataset.testimonialsReady==="true"||typeof window.Swiper!=="function")return;
    const slider=block.querySelector(".testimonialSwiper");if(!slider)return;block.dataset.testimonialsReady="true";
    const next=slider.querySelector(".testimonial-next"),prev=slider.querySelector(".testimonial-prev");
    const autoplay=slider.dataset.autoplay==="true"?{delay:Number(slider.dataset.delay||4000),disableOnInteraction:false}:false;
    new window.Swiper(slider,{slidesPerView:1,spaceBetween:20,loop:slider.dataset.loop==="true",rewind:slider.dataset.loop!=="true",autoplay:autoplay,autoHeight:false,centeredSlides:false,navigation:next&&prev?{nextEl:next,prevEl:prev}:undefined});
  }
  function all(root){(root||document).querySelectorAll(".wp-block-my-theme-testimonials").forEach(init);}
  if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",function(){all(document);});else all(document);
})();
