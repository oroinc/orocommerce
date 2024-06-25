export default class IconsService {
    constructor({baseSvgSpriteUrl}) {
        this.baseSvgSpriteUrl = baseSvgSpriteUrl;
        this.iconsDataCache = {};
        this.memeType = 'image/svg+xml';
    }

    getUrl(theme) {
        return this.baseSvgSpriteUrl.replace('__theme__', theme);
    }

    getSvgIconUrl(iconId = '', themeName) {
        return `${this.getUrl(themeName)}#${iconId}`;
    }

    async getAndParseIconCollection({theme}) {
        const {name} = theme;
        const providerUrl = this.getUrl(name);

        if (this.iconsDataCache[providerUrl]) {
            return this.iconsDataCache[providerUrl];
        }

        const response = await fetch(providerUrl);

        const svg = new DOMParser().parseFromString(
            await response.text(),
            this.memeType
        );

        const data = [...svg.querySelectorAll('symbol')].map(symbol => {
            return {
                id: symbol.id,
                themeName: name
            };
        });

        this.iconsDataCache[providerUrl] = data;
        return data;
    }

    async isIconAvailable({iconId, theme}) {
        try {
            const icons = await this.getAndParseIconCollection({theme});

            return icons.find(({id}) => id === iconId);
        } catch (e) {
            return false;
        }
    }

    isSvgIconsSupport(activeTheme) {
        return activeTheme.svgIconsSupport;
    }
}
