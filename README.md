# Method Gallery

This plugin adds filterable galleries to WordPress, called via shortcode. CMB2 and a Bootstrap 5 theme are required.

---

## v2.0.0-beta2

This release includes several bugfixes and improvements. As of this release, the legacy layout class is no longer included in the plugin.

Changes:
* Format-specific options are now conditionally displayed for the selected gallery format (swiper or grid).
* Slide and grid image sizes and aspect ratios can now be set on a gallery-by-gallery basis. The default option is to use the applicable global size and ratio set in plugin options.
* Image size checks are now performed via a custom method instead of has_image_size(), as the function does not take core image sizes (medium, large, etc) into account and would return false when core sizes were passed.
* For swiper galleries with lightboxes enabled, slide images are no longer grouped together into the same lightbox gallery to prevent 2 different gallery navigations from existing (seemed confusing from a UX standpoint).
* Improved option descriptions in the gallery editor.
* Tweaked padding for the shortcode metabox.
* The method_gallery_get_image_size_array() function now accepts an optional argument for easily passing an empty label (ex: "Select a size...")

---

## v2.0.0-beta1

This is the initial beta for a rewrite of the Method Gallery plugin. This release adds a post type and custom taxonomy for building filterable galleries in a variety of different formats, with lightbox support. It is not recommended that you upgrade to this release if you are using the legacy Method_Gallery class, as conflicting versions of swiper will be loaded. The option to only enqueue the old version will be included in a later release.