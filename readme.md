# Open Science Badges

A plugin for [Open Journal Systems](https://pkp.sfu.ca/software/ojs) (OJS) that allows editors to assign the Center for Open Science's [Open Science Badges](https://www.cos.io/initiatives/badges) to qualifying publications.

!['Screenshot of an example of the badge display for an article'](screenshot.png)

## How to Use

This plugin requires OJS 3.3.x. Once you have installed and activated the plugin, follow these steps to begin showing badges on your site.

1. **Login** as an editor or any role with editorial access to submissions.
2. Go to **Submissions** and open any submission in the Copyediting or Production stage.
3. Go to **Publication > Metadata**.
4. Enter the disclosure statement for one or more of the metadata fields for open badges, **Open Data**, **Open Materials**, **Preregistered**, or **Preregistered Plus**, and save the metadata form.
5. Click **Preview** to view the badges on the article.

You can change how and where the badges are displayed by going to **Settings > Plugins > Open Science Badges > Settings**.

## When should I use Open Science Badges?

The [Center for Open Science](https://www.cos.io/initiatives/badges) provides guidance on the criteria for awarding Open Science Badges and examples of disclosure statements that must accompany the badges.

## Package and Release

Update the version and date in the `version.xml` file.

```
<release>[version]</release>
<date>[YYYY-MM-DD]</date>
```

Create a `.tar.gz` package of this plugin by running the following command in the directory above the plugin.

```
tar -czf openScienceBadges-<version>.tar.gz --exclude-ignore=.tarignore openScienceBadges
```

## Credits

This plugin was created thanks to funding from SLUB Dresden for the [Individualize Theme by Publia](https://github.com/NateWr/individualizeTheme).