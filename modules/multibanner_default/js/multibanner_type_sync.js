/**
 * Iniatiate sliding banner.
 */
(function ($, Drupal) {
  $(function() {
		$( '.view-display-id-block_sync .slider-pro' ).sliderPro({
			width: 1000,
			height: 370,
			orientation: 'horizontal',
			loop: true,
			arrows: true,
			fadeArrows: false,
			buttons: false,
			thumbnailsPosition: 'right',
			thumbnailPointer: true,
			thumbnailWidth: 360,
			//thumbnailArrows: true,
			fadeThumbnailArrows: false,
			breakpoints: {
				800: {
					thumbnailsPosition: 'bottom',
					thumbnailWidth: 270,
					thumbnailHeight: 100
				},
				500: {
					thumbnailsPosition: 'bottom',
					thumbnailWidth: 120,
					thumbnailHeight: 50
				}
			}
		});
  });
})(window.jQuery, window.Drupal, window.drupalSettings);

