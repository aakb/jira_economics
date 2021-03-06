let Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('easy_admin', './assets/js/easy_admin.js')
    .addEntry('app', './assets/js/app.js')
    .addEntry('billing', './bundles/Billing/Resources/assets/index.js')
    .addEntry('expense', './bundles/Expense/Resources/assets/index.js')
    .addEntry('create_project', './bundles/CreateProject/Resources/assets/index.js')
    .addEntry('graphic_service_order', './bundles/GraphicServiceOrder/Resources/assets/index.js')
    .addEntry('planning', './bundles/Planning/Resources/assets/planning.js')
    .addEntry('planning_board', './bundles/Planning/Resources/assets/planningBoard.js')
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .enableSassLoader()
    .enableReactPreset()
    .enableVueLoader()
    .enableVersioning()
    .enablePostCssLoader()
    .autoProvidejQuery()
    .copyFiles({
        from: './assets/images',
        to: 'images/[path][name].[ext]'
    })
;

module.exports = [Encore.getWebpackConfig()];
