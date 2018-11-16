var path = require('path');

module.exports = {
    entry: './src/js/app.js',
    output: {
        filename: 'bundle.js',
        path: path.resolve(__dirname, '/public/assert/js')
    },
    module: {
        rules: [
            {
                test: /\.s?css$/,
                use: ['style-loader', 'css-loader']
            },

        ]
    }
};