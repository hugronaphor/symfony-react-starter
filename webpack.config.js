const Encore = require('@symfony/webpack-encore');

const path = require("path");
Encore.setOutputPath("public/build/")
    .setPublicPath("/build")

    .addEntry("app", "./assets/app.js")
    .addEntry("react", "./assets/react/index.tsx")
    .enableReactPreset()
    .enableTypeScriptLoader()
    .enablePostCssLoader()
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    .addAliases({
        "@": path.resolve(__dirname, "assets/react"),
    });

module.exports = Encore.getWebpackConfig();
