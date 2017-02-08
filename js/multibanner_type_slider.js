/**
 * Iniatiate sliding banner.
 */
(function ($, Drupal) {
  $(function() {
    $('.view-display-id-block_default > .view-content > div').unslider({
      animation: 'horizontal',
      arrows: true,
      autoplay: false,
      delay: 5000,
      infinite: true,
      nav: true,
      speed: 500
    })
  });
})(window.jQuery, window.Drupal, window.drupalSettings);
