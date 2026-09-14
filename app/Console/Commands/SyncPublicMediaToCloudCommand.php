<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\Finder;

class SyncPublicMediaToCloudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:sync-to-cloud
        {--dry-run : Yalnızca yüklenecek dosyaları listeler, gerçek yükleme yapmaz}
        {--force : Hedefte zaten var olan dosyaları da yeniden yükler}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kaynak: yerel storage/app/public klasörü. Hedef: "public" diskin PUBLIC_DISK_DRIVER=s3 ile işaret ettiği Laravel Cloud Object Storage bucket\'ı. Bu iki uç asla aynı olamaz (kaynak her zaman fiziksel yerel klasördür).';

    public function handle(): int
    {
        $localRoot = storage_path('app/public');

        if (! is_dir($localRoot)) {
            $this->error("Yerel klasör bulunamadı: {$localRoot}");

            return self::FAILURE;
        }

        $driver = config('filesystems.disks.public.driver');
        $this->info("Kaynak (source): {$localRoot} (yerel dosya sistemi)");
        $this->info("Hedef (destination) disk sürücüsü: public => {$driver}");

        // Gerçek yükleme (dry-run değil) yalnızca hedef gerçekten bir S3/R2
        // bucket'ı olduğunda yapılabilir. Aksi halde hedef de yerel diskin
        // aynısı olur ve "yükleme" kendi üzerine no-op bir kopyaya döner -
        // bunu sessizce izin vermek yerine sert şekilde reddediyoruz.
        if (! $this->option('dry-run') && $driver !== 's3') {
            $this->error(
                "Hedef disk 's3' değil, '{$driver}'. Bu durumda kaynak (yerel storage/app/public) ile hedef ".
                'fiziksel olarak AYNI klasör olurdu ve yükleme anlamsız/tehlikeli bir no-op olurdu. '.
                'Gerçek yükleme için önce .env / Cloud environment değişkenlerinde PUBLIC_DISK_DRIVER=s3 '.
                'ayarla (Laravel Cloud, bucket bağladığında AWS_* değişkenlerini otomatik enjekte eder). '.
                'Dosya listesini önizlemek için --dry-run kullanabilirsin.'
            );

            return self::FAILURE;
        }

        if (! $this->option('dry-run')) {
            foreach (['bucket' => 'AWS_BUCKET', 'key' => 'AWS_ACCESS_KEY_ID', 'secret' => 'AWS_SECRET_ACCESS_KEY'] as $configKey => $envName) {
                if (blank(config("filesystems.disks.public.{$configKey}"))) {
                    $this->error("config('filesystems.disks.public.{$configKey}') boş. {$envName} ortam değişkeninin ayarlı olduğundan emin ol.");

                    return self::FAILURE;
                }
            }
        }

        $disk = Storage::disk('public');

        $finder = (new Finder)->in($localRoot)->files();

        $total = 0;
        $uploaded = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($finder as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            $total++;

            if (! $this->option('force') && $disk->exists($relativePath)) {
                $skipped++;
                $this->line("  atlandı (zaten var): {$relativePath}");

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  [dry-run] yüklenecek: {$relativePath}");

                continue;
            }

            try {
                $stream = fopen($file->getPathname(), 'r');
                // Görünürlük (public/private) bucket seviyesinde ayarlanır;
                // Cloudflare R2 nesne bazlı ACL'i desteklemiyor ve visibility
                // parametresi geçilirse "NotImplemented" hatası döner.
                $disk->put($relativePath, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $uploaded++;
                $this->line("  yüklendi: {$relativePath}");
            } catch (\Throwable $e) {
                $failed++;
                $this->error("  HATA ({$relativePath}): {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Toplam dosya: {$total} | Yüklendi: {$uploaded} | Atlandı: {$skipped} | Hata: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
