import { HttpInterceptorFn } from '@angular/common/http';

export const ngrokInterceptor: HttpInterceptorFn = (req, next) => {
  if (req.url.includes('ngrok-free.app')) {
    const clonedRequest = req.clone({
      headers: req.headers.set('ngrok-skip-browser-warning', 'true')
    });
    return next(clonedRequest);
  }
  return next(req);
};
