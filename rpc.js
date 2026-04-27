const KEY = CryptoJS.enc.Utf8.parse('1191ADF18489D8DA');
const IV  = CryptoJS.enc.Utf8.parse('5E9B755A8B674394');

function enc(o){ return CryptoJS.AES.encrypt(JSON.stringify(o), KEY, {iv:IV}).toString(); }
function dec(s){ return JSON.parse(CryptoJS.AES.decrypt(s, KEY, {iv:IV}).toString(CryptoJS.enc.Utf8)); }

async function rpc(method,params){
    const pack = {
        '!version':6,
        client_version:'common_6.00.001.0',
        id:1,
        jsonrpc:'2.0',
        method:method,
        params:enc(params)
    };
    const r = await fetch('index.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify(pack)
    });
    const j = await r.json();
    return dec(j.data);
}