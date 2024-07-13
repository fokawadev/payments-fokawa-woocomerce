const settings = window.wc.wcSettings.getSetting('fokawapay_gateway_data', {});
const label = window.wp.htmlEntities.decodeEntities(settings.title) || window.wp.i18n.__('FokawaPay', 'fokawapay_gateway');
var isLoaded = false;
const savedCrypto = localStorage.getItem('selectedCrypto');
    
    const updateOrderCustomField = (phpamount, coin) => {
        fetch(wc_cart_params.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'update_order_custom_field',
                phpamount: phpamount,
                coin: coin,
            })
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
    };

    const restore = () => {
        const savedCrypto = localStorage.getItem('selectedCrypto');
        if (savedCrypto) {
            const selectElement = document.getElementById('coin');
            selectElement.value = savedCrypto;
            const event = new Event('change', { bubbles: true });
            selectElement.dispatchEvent(event);
        }
    }

    const crypto = [ 
        { icon: 'icon.png', text: 'FOKAWA (FKWT)', value: 'FKWT1799' },
        { icon: 'usdt.png', text: 'Tether (USDT)', value: 'USDT' },
        { icon: 'bnb.png', text: 'Binance Coin (BNB)', value: 'BNB' },
        { icon: 'ethereum.png', text: 'ETHEREUM (ETH)', value: 'ETH' },
        { icon: 'bitcoin.png', text: 'BITCOIN (BTC)', value: 'BTC' },
        { icon: 'solana.png', text: 'SOLANA (SOL)', value: 'SOL' },
        { icon: 'xrp.png', text: 'Ripple (XRP)', value: 'XRP' },
        { icon: 'tron.png', text: 'TRON (TRX)', value: 'TRX' },
        { icon: 'ton.png', text: 'The Open Network (TON)', value: 'TON' }
    ];
    
    const options = crypto.map(item => 
        window.wp.element.createElement('option', { value: item.value, 'data-icon': item.icon }, item.text)
    );

    const handleSelectChange = (event) => {
        const selectedValue = event.target.value;
        if (!selectedValue) return;  // Ensure the event is triggered only by coin selection

        localStorage.setItem('selectedCrypto', selectedValue);
        const selectedCrypto = crypto.find(item => item.value === selectedValue);

        if (selectedCrypto) {
            document.getElementById('crypto_icon').style.display = "none";
            document.getElementById('crypto_name').textContent = "processing...";
            document.getElementById('crypto_amount').textContent = "processing..."; 
            
            fetch(`https://payments.fokawa.com/apiv2/rate/?phpamount=${wc_cart_params.cart_total}&coin=${selectedValue}`)
                .then(response => response.json())
                .then(data => {
                    const j = data;
                    const c = selectedValue === 'FKWT1799' ? 'FKWT' : selectedValue;
                    const scientificNumber = parseFloat(j.conversion);
                    const dnum = ["USDT", "FKWT1799", "XRP"].includes(selectedValue) ? 2 : 8;
                    const decimalNumber = scientificNumber.toFixed(dnum); 
                    
                    if (data && j.conversion) {
                        document.getElementById('crypto_icon').style.display = "inline";
                        document.getElementById('crypto_icon').src = settings.dir + selectedCrypto.icon;
                        document.getElementById('crypto_amount').textContent = `${decimalNumber} ${c}`;
                        document.getElementById('crypto_name').textContent = selectedCrypto.text; 
                        
                        updateOrderCustomField(wc_cart_params.cart_total, selectedValue);
                    }
                })
                .catch(error => console.error('Error fetching rate:', error));
        }
    };

    if (!isLoaded) {
        isLoaded = true;
        setTimeout(() => { 
            restore();
        }, 300);
    }
    
const Content = () => { 

    

    return window.wp.element.createElement('fieldset', { className: 'checkout-summary' },
        window.wp.element.createElement('div', { className: 'coupon-section' },
            window.wp.element.createElement('label', { htmlFor: 'coin' }, 'Please select a cryptocurrency.'),
            window.wp.element.createElement('select', { id: 'coin', onChange: handleSelectChange, name: 'coin' }, options)
        ),
        window.wp.element.createElement('div', { className: 'subtotal-section' },
            window.wp.element.createElement('p', null, 'PHP Amount'),
            window.wp.element.createElement('span', { className: 'amount', id: "php_amount" }, `₱${wc_cart_params.cart_total}`)
        ),
        window.wp.element.createElement('div', { className: 'shipping-section' },
            window.wp.element.createElement('p', null, 'Selected'),
            window.wp.element.createElement('span', { className: 'amount' }, 
                window.wp.element.createElement('img', { id: "crypto_icon", src: '', alt: 'Crypto icon' }),
                window.wp.element.createElement('p', { id: "crypto_name" })
            ),
            window.wp.element.createElement('p', { className: 'shipping-details' }, ' '),
            window.wp.element.createElement('p', { className: 'shipping-location' }, ' ')
        ),
        window.wp.element.createElement('div', { className: 'total-section' },
            window.wp.element.createElement('p', null, 'To send:'),
            window.wp.element.createElement('span', { className: 'amount', id: "crypto_amount" }, '---')
        )
    );
};

const Block_Gateway = {
    name: 'fokawapay_gateway',
    label: label,
    content: window.wp.element.createElement(Content, null),
    edit: window.wp.element.createElement(Content, null),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);