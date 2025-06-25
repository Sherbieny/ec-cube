const path = require("path");
const webpack = require("webpack");
const glob = require("glob");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

// Collect plugin assets by scope and type
function getPluginEntries(scope, type) {
    return glob
        .sync(`app/Plugin/*/Resource/assets/${type}/${scope}/bundle/*.${type}`)
        .map((p) => `./${p}`);
}

const pluginFrontJS = getPluginEntries("default", "js");
const pluginAdminJS = getPluginEntries("admin", "js");
const pluginFrontSCSS = getPluginEntries("default", "scss");
const pluginAdminSCSS = getPluginEntries("admin", "scss");
const pluginSharedJS = getPluginEntries("shared", "js");

// Core + plugin assets per scope
const frontEntry = [
    "./html/template/default/assets/js/bundle.js",
    ...pluginFrontJS,
    ...pluginFrontSCSS,
    ...pluginSharedJS,
];

const adminEntry = [
    "./html/template/admin/assets/js/bundle.js",
    ...pluginAdminJS,
    ...pluginAdminSCSS,
    ...pluginSharedJS,
];

const entry = {
    front: frontEntry,
    admin: adminEntry,
    install: "./html/template/install/assets/js/bundle.js",
};

module.exports = {
    mode: "production",
    entry,
    devtool: "source-map",
    output: {
        path: path.resolve(__dirname, "html/bundle"),
        filename: "[name].bundle.js",
    },
    resolve: {
        alias: {
            jquery: path.join(__dirname, "node_modules", "jquery"),
            // Add `shared` alias for shared js files
            shared: path.resolve(__dirname, "html/template/shared/assets/js"),
        },
    },
    module: {
        rules: [
            {
                test: /\.css$/,
                use: ["style-loader", "css-loader"],
            },
            {
                test: /\.(png|jpe?g|svg|gif|eot|woff2?|ttf)$/,
                use: ["url-loader"],
            },
            {
                test: /\.js$/,
                use: {
                    loader: "babel-loader",
                    options: {
                        presets: ["@babel/preset-env"],
                    },
                },
                exclude: /node_modules/,
            },
            {
                test: /\.scss$/,
                use: [MiniCssExtractPlugin.loader, "css-loader", "sass-loader"],
                exclude: /node_modules/,
            },
        ],
    },
    plugins: [
        new webpack.ProvidePlugin({
            $: "jquery",
            jQuery: "jquery",
            "window.jQuery": "jquery",
        }),
        new MiniCssExtractPlugin({
            filename: "[name].bundle.css",
        }),
    ],
};
