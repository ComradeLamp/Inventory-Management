document.addEventListener('DOMContentLoaded', function () {
    //Initialize Bootstrap Carousel
    var carouselElement = document.querySelector('#heroCarousel');
    if (carouselElement) {
        var carousel = new bootstrap.Carousel(carouselElement, {
            interval: 5000, //Slide interval in ms
            ride: 'carousel'
        });
    }

    //Bootstrap Form Validation
    var forms = document.querySelectorAll('.needs-validation');

    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    });
});
