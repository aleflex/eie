# Servidor de producción optimizado con Nginx
FROM nginx:alpine

# Copiar los archivos ya compilados localmente en el host
COPY ./dist/frontend/browser /usr/share/nginx/html

# Copiar la configuración de redirección de SPA
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
