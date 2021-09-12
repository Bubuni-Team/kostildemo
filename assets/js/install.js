/**
 * Kostildemo project.
 *
 * Purpose: performs a frontend logic for setup process.
 */
(function (window, document) {
    document.addEventListener('DOMContentLoaded', function () {
        const httpUrl = document.documentElement.dataset.publicUrl;
        const post = function (content) {
            return fetch(httpUrl + '?controller=install', {
                method: 'POST',
                body: JSON.stringify(content),
                headers: {
                    'Content-Type': 'application/json'
                }
            });
        };

        const random = {
            int: function (min, max) {
                return min + Math.floor(Math.random() * (max - min));
            },

            key: function (length = 32) {
                let key = '';
                let min = 0, max = 0;
                for (let i = 0; i < length; ++i) {
                    switch (this.int(0, 2)) {
                        case 0: // integer
                            min = 48;
                            max = 57;
                            break;

                        case 1: // uppercase english letters
                            min = 65;

                        case 2: // lowercase english letters
                            min = 97;
                            max = min + 25;
                            break;
                    }

                    key += String.fromCharCode(this.int(min, max));
                }

                return key;
            }
        };

        const mapPresets = JSON.parse(document.querySelector('#game-presets').innerHTML);
        (window.initializeTemplateRelatedComponents || function () { })();

        window.vue = new Vue({
            el: '#install-root',
            data: {
                db: {
                    host: 'localhost',
                    port: 3306,

                    user: '',
                    password: '',
                    dbname: '',

                    errorMessage: '',
                    isSuccess: undefined
                },

                options: {
                    siteName: '',
                    chunkSize: {
                        method: 'auto',
                        count: 2
                    },
                    publicUrl: httpUrl,
                    mapPresets: []
                },

                servers: [
                    {
                        name: '',
                        address: '',
                        key: random.key(32)
                    }
                ],

                mapDict: [
                    {
                        name: '',
                        title: ''
                    }
                ],
                administrators: [
                    {
                        value: ''
                    }
                ],

                metadata: {
                    mapPresets: mapPresets
                }
            },

            methods: {
                checkDatabaseCredentials() {
                    if (!this.db.host.length || !this.db.user.length || !this.db.dbname.length) {
                        return;
                    }

                    post({
                        command: 'verify_database_credentials',
                        credentials: this.db
                    })
                        .then(response => response.json())
                        .then(response => {
                            this.db.isSuccess = response.success;
                            this.db.errorMessage = response.user_friendly_msg;
                        });
                },

                install() {
                    if (!this.db.isSuccess) {
                        return;
                    }

                    const options = this.options;
                    options.chunkSize =
                        (options.chunkSize.method === 'auto' ? '' : options.chunkSize.count.toString()) +
                        options.chunkSize.method;

                    const installRequest = {
                        command: 'run',
                        mapNames: this.mapDict.reduce((map, kv) => {
                            kv.title = kv.title.trim();
                            kv.name = kv.name.trim();

                            if (kv.title.length && kv.name.length) {
                                map[kv.name] = kv.title;
                            }

                            return map;
                        }, {}),
                        system: options,
                        db: {
                            host: this.db.host,
                            port: parseInt(this.db.port) || 3306,
                            user: this.db.user,
                            password: this.db.password,
                            dbname: this.db.dbname
                        },

                        administrators: this.administrators,
                        servers: this.servers
                    };

                    post(installRequest)
                        .then(result => result.json())
                        .then(result => window.location = result.redirect);
                },

                // These methods is developed by Black_Yuzia.
                // Thanks for help, keyed faggot :3
                createItem(array, object) {
                    array.push(object);
                },
                deleteItem(array, index) {
                    array.splice(index, 1)
                },
                checkFields(array, currentItemIndex, keyFields = {}, includeValueKey = true, excludeKeys = []) {
                    const currentItem = array[currentItemIndex] || {};
                    const latestItemIndex = array.length - 1;

                    if (includeValueKey) {
                        keyFields["value"] = "";
                    }

                    const keyFieldsForValidating = Object.keys(keyFields);

                    let isFilled = false;
                    for (let keyField of keyFieldsForValidating) {
                        if (excludeKeys.includes(keyField)) continue;
                        isFilled = isFilled || currentItem[keyField];
                    }

                    if (currentItemIndex !== latestItemIndex) {
                        if (!isFilled && array.length > 1) this.deleteItem(array, currentItemIndex);
                    }
                    else {
                        if (isFilled) this.createItem(array, keyFields)
                        else if (array.length > 1) this.deleteItem(array, latestItemIndex)
                    }
                },

                intRandom(min, max) {
                    return random.int(min, max);
                },

                generateKey(length) {
                    return random.key(length);
                }
            }
        });
    });
})(window, document);
