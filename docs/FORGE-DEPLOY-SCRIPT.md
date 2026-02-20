# Forge / Awapanel Deploy

**Tüm kurulum (Web Directory, Environment, deploy script):** **[SETUP.md](SETUP.md)**

**Forge’da Deploy Script alanına yapıştırılacak tek satır:** **[FORGE-DEPLOY-YAPISTIR.txt](FORGE-DEPLOY-YAPISTIR.txt)**

Panelde deploy script alanına **sadece** şunu yazın (site adı/path yazmayın; Forge otomatik verir):

```bash
cd $FORGE_SITE_PATH && bash deploy.sh
```

Farklı path’li (örn. `depo.awapanel.com`) script kullanırsanız yanlış dizine deploy olur ve 403 veya çalışmama görülür.
