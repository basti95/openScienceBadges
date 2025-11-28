{**
 * The template when the badges are added to the article
 * page with one of the default hooks.
 *
 * Templates::Article::Details
 * Templates::Article::Main
 *
 * This template is designed to mimic the HTML/CSS structure
 * similar blocks on the article page.
 *}
<div class="item opensciencebadges-article-details">
  <h2 class="label">
    {translate key="plugins.generic.openScienceBadges.displayName"}
  </h2>
  <div class="value">
    {$osbBadgesDisplay}
  </div>
</div>