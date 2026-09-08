import { Component, OnInit } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { Capacitor } from '@capacitor/core';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet],
  templateUrl: './app.component.html',
  styleUrl: './app.component.css'
})
export class AppComponent implements OnInit {
  title = 'frontend';

  ngOnInit(): void {
    if (Capacitor.isNativePlatform()) {
      document.documentElement.classList.add('capacitor-native');
      if (document.body) document.body.classList.add('capacitor-native');
    }
  }
}
