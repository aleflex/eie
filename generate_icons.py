import os
from PIL import Image, ImageDraw

LOGO_PATH = 'frontend/src/assets/imagenes/logo.jpg'

def generate_icons():
    if not os.path.exists(LOGO_PATH):
        print(f"Error: {LOGO_PATH} not found!")
        return

    orig_img = Image.open(LOGO_PATH).convert('RGBA')
    w, h = orig_img.size

    # Crop tightly around the emblem
    # Bounding box of emblem was (236, 272, 1360, 1304), center is roughly (798, 788)
    cx, cy = 798, 788
    crop_size = 1180
    left = max(0, cx - crop_size // 2)
    top = max(0, cy - crop_size // 2)
    right = min(w, left + crop_size)
    bottom = min(h, top + crop_size)
    cropped_emblem = orig_img.crop((left, top, right, bottom))

    # Also make a transparent version where outer off-white background is made transparent
    bg_color = (245, 241, 238)
    transparent_emblem = cropped_emblem.copy()
    datas = transparent_emblem.getdata()
    new_data = []
    for item in datas:
        # Check distance to background color
        dist = sum(abs(item[i] - bg_color[i]) for i in range(3))
        if dist < 22:
            new_data.append((255, 255, 255, 0))
        elif dist < 38:
            # smooth edge antialiasing
            alpha = int(255 * ((dist - 22) / 16))
            new_data.append((item[0], item[1], item[2], alpha))
        else:
            new_data.append(item)
    transparent_emblem.putdata(new_data)

    print("Emblem cropped and transparency processed.")

    # 1. FAVICON FOR WEB
    # Generate multi-resolution ICO
    ico_sizes = [(16, 16), (32, 32), (48, 48), (64, 64), (128, 128), (256, 256)]
    ico_path = 'frontend/src/favicon.ico'
    transparent_emblem.save(ico_path, format='ICO', sizes=ico_sizes)
    print(f"Generated {ico_path}")

    # Also save as PNG
    png_fav = 'frontend/src/assets/imagenes/favicon.png'
    transparent_emblem.resize((192, 192), Image.Resampling.LANCZOS).save(png_fav, format='PNG')
    print(f"Generated {png_fav}")

    # Copy to dist if exists
    dist_fav = 'frontend/dist/frontend/browser/favicon.ico'
    if os.path.exists(os.path.dirname(dist_fav)):
        transparent_emblem.save(dist_fav, format='ICO', sizes=ico_sizes)
        print(f"Updated {dist_fav}")

    # Backend public favicon
    be_fav = 'backend/public/favicon.ico'
    if os.path.exists(os.path.dirname(be_fav)):
        transparent_emblem.save(be_fav, format='ICO', sizes=ico_sizes)
        print(f"Updated {be_fav}")

    # 2. ANDROID MIPMAP LAUNCHER ICONS
    android_res_dirs = [
        'frontend/android/app/src/main/res',
        'android/app/src/main/res'
    ]

    mipmap_configs = {
        'mipmap-mdpi': {'launcher': 48, 'foreground': 108, 'safe': 74},
        'mipmap-hdpi': {'launcher': 72, 'foreground': 162, 'safe': 110},
        'mipmap-xhdpi': {'launcher': 96, 'foreground': 216, 'safe': 148},
        'mipmap-xxhdpi': {'launcher': 144, 'foreground': 324, 'safe': 220},
        'mipmap-xxxhdpi': {'launcher': 192, 'foreground': 432, 'safe': 294},
    }

    def make_round_icon(img, size):
        resized = img.resize((size, size), Image.Resampling.LANCZOS)
        mask = Image.new('L', (size, size), 0)
        draw = ImageDraw.Draw(mask)
        draw.ellipse((0, 0, size - 1, size - 1), fill=255)
        output = Image.new('RGBA', (size, size), (255, 255, 255, 0))
        output.paste(resized, (0, 0), mask)
        return output

    def make_square_rounded_icon(img, size, radius=None):
        if radius is None:
            radius = int(size * 0.18)
        resized = img.resize((size, size), Image.Resampling.LANCZOS)
        mask = Image.new('L', (size, size), 0)
        draw = ImageDraw.Draw(mask)
        draw.rounded_rectangle((0, 0, size - 1, size - 1), radius=radius, fill=255)
        output = Image.new('RGBA', (size, size), (255, 255, 255, 0))
        output.paste(resized, (0, 0), mask)
        return output

    def make_foreground_icon(img, canvas_size, safe_size):
        resized = img.resize((safe_size, safe_size), Image.Resampling.LANCZOS)
        canvas = Image.new('RGBA', (canvas_size, canvas_size), (0, 0, 0, 0))
        offset = (canvas_size - safe_size) // 2
        canvas.paste(resized, (offset, offset), resized)
        return canvas

    for res_dir in android_res_dirs:
        if not os.path.exists(res_dir):
            continue

        for folder, cfg in mipmap_configs.items():
            dest_dir = os.path.join(res_dir, folder)
            os.makedirs(dest_dir, exist_ok=True)

            l_size = cfg['launcher']
            fg_size = cfg['foreground']
            safe_size = cfg['safe']

            # ic_launcher.png
            launcher = make_square_rounded_icon(cropped_emblem, l_size)
            launcher.save(os.path.join(dest_dir, 'ic_launcher.png'), format='PNG')

            # ic_launcher_round.png
            launcher_round = make_round_icon(cropped_emblem, l_size)
            launcher_round.save(os.path.join(dest_dir, 'ic_launcher_round.png'), format='PNG')

            # ic_launcher_foreground.png
            fg = make_foreground_icon(transparent_emblem, fg_size, safe_size)
            fg.save(os.path.join(dest_dir, 'ic_launcher_foreground.png'), format='PNG')

            print(f"Generated launcher icons for {dest_dir}")

        # 3. ANDROID SPLASH SCREENS
        splash_configs = {
            'drawable': (480, 320),
            'drawable-land-mdpi': (480, 320),
            'drawable-land-hdpi': (800, 480),
            'drawable-land-xhdpi': (1280, 720),
            'drawable-land-xxhdpi': (1600, 960),
            'drawable-land-xxxhdpi': (1920, 1280),
            'drawable-port-mdpi': (320, 480),
            'drawable-port-hdpi': (480, 800),
            'drawable-port-xhdpi': (720, 1280),
            'drawable-port-xxhdpi': (960, 1600),
            'drawable-port-xxxhdpi': (1280, 1920),
        }

        for folder, (sw, sh) in splash_configs.items():
            splash_dir = os.path.join(res_dir, folder)
            os.makedirs(splash_dir, exist_ok=True)

            # Splash canvas is clean white (#FFFFFF)
            splash = Image.new('RGBA', (sw, sh), (255, 255, 255, 255))
            
            # Emblem size: 45% of minimum screen dimension
            target_emblem_size = int(min(sw, sh) * 0.45)
            emblem_resized = transparent_emblem.resize((target_emblem_size, target_emblem_size), Image.Resampling.LANCZOS)
            
            # Center the emblem
            ox = (sw - target_emblem_size) // 2
            oy = (sh - target_emblem_size) // 2
            splash.paste(emblem_resized, (ox, oy), emblem_resized)
            
            splash_path = os.path.join(splash_dir, 'splash.png')
            splash.save(splash_path, format='PNG')
            print(f"Generated splash for {splash_path}")

    print("\nALL ICONS AND SPLASH SCREENS GENERATED SUCCESSFULLY!")

if __name__ == '__main__':
    generate_icons()
