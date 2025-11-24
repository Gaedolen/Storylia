document.addEventListener('DOMContentLoaded', () => {
    const carouselElement = document.getElementById('coupsDeCoeurCarousel');
    const carousel = new bootstrap.Carousel(carouselElement, {
        interval: 3000,
        ride: 'carousel',
        pause: false
    });

    let autoTimeout;

    const resetAutoSlide = () => {
        clearTimeout(autoTimeout);
        autoTimeout = setTimeout(() => {
            carousel.cycle();
        }, 15000); // 15 secondes après dernière interaction
    };

    const stopAutoSlide = () => {
        carousel.pause();
        resetAutoSlide();
    };

    // Dès qu'on clique sur une flèche
    const prevBtn = carouselElement.querySelector('.carousel-control-prev');
    const nextBtn = carouselElement.querySelector('.carousel-control-next');

    [prevBtn, nextBtn].forEach(btn => {
        btn.addEventListener('click', stopAutoSlide);
    });

    // Redémarre auto après 15s d'inactivité
    carouselElement.addEventListener('mouseenter', stopAutoSlide);
    carouselElement.addEventListener('mouseleave', resetAutoSlide);

    // Lancer le timer initial
    resetAutoSlide();
});
