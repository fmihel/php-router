import sodium from "libsodium-wrappers";

class Crypto {

   constructor(request, session) {

      this.request = request;
      this.session = session;

      this.request.addInterceptor(async (ev, config) => {
         if (!this.session.enable()) {
            return config;
         }

         if (config == null) {
            return config;
         }
         if (config?.skipCrypto) {
            return config;
         }

         if (!this.session.data.pub) {
            throw new Error("pub key is empty");
         }


         if (ev === 'before') {
            config.data = await this.encrypt(config.data, this.session.data.pub);
            config.vars = { ...config.vars, enc: true }
         }

         return config;
      });
   }

   async encrypt(dataObject, serverPublicKeyBase64) {
      // дожидаемся инициализации WebAssembly модуля
      await sodium.ready;

      // Декодируем публичный ключ сервера из Base64 в Uint8Array
      // Используем вариант ORIGINAL, чтобы совпасть с PHP base64_encode()
      const publicKey = sodium.from_base64(
         serverPublicKeyBase64,
         sodium.base64_variants.ORIGINAL
      );

      // Превращаем данные в строку и затем в байты
      const messageBytes = sodium.from_string(JSON.stringify(dataObject));

      // Шифруем с помощью crypto_box_seal
      const encryptedBytes = sodium.crypto_box_seal(messageBytes, publicKey);

      // Возвращаем в Base64 для отправки по HTTP (JSON / FormData)
      return sodium.to_base64(
         encryptedBytes,
         sodium.base64_variants.ORIGINAL
      );
   }
}

export { Crypto };