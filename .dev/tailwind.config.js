const colors = require('./moloni.colors');

module.exports = {
    mode: 'jit',
    content: [
        './../src/**/*.php',
    ],
    safelist: [{
        pattern: /(bg|text)-(primary|secondary|success|warning|critical|error|alternate|subalternate|neutral)-([01]?[0-9][0-9])$/,
    }],
    corePlugins: {
        backdropOpacity: false,
        backgroundOpacity: false,
        borderOpacity: false,
        divideOpacity: false,
        ringOpacity: false,
        textOpacity: false
    },
    theme: {
        extend: {
            colors: {
                'cyan': '#0067BF',
                'navy': {
                    '50': '#eaebed',
                    'default': '#303A4DFF'
                },
                'navy-10': "303A4DFF",
                ...colors
            },
            maxHeight: {
                '(screen-18)': 'calc(100vh - 4.5rem)'
            },
            maxWidth: {
                '8xl': '90rem',
            },
            inset: {
                '18': '4.5rem'
            },
            fontFamily: {
                'sans': ['Open Sans'],
                'poppins': ['Poppins']
            },
            textColor: {
                "cyan": '#0067BF',
                "navy": '#303A4DFF'
            }
        },
    },
    plugins: [
        require('@tailwindcss/typography'),
    ],
}
