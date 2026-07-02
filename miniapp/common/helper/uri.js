const parseUrl = url => {
    const sp = url.indexOf('?');
    return {
        baseUrl: sp > -1 ? url.slice(0, sp) : url,
        params: sp > -1 ? parseQuery(url.slice(sp+1)) : {}
    }
}

const parseQuery = query => {
    if (query === null || query === '' || query === void 0) {
        return {};
    }
    return query.split('&').map(p => {
        return p.split('=');
    }).reduce((p, c) => {
        p[c[0]] = c[1];
        return p;
    }, {})
}

export const buildUrl = (baseUrl, params) => {
    const res = parseUrl(baseUrl);
    for (const key of Object.keys(params)) {
        res.params[key] = params[key];
    }
    let query = [];
    for (const key of Object.keys(res.params)) {
        query.push(`${key}=${res.params[key]}`);
    }
    return res.baseUrl + '?' + query.join('&');
}