<?php

use packager\Event;
use packager\Package;
use packager\cli\Console;
use php\io\File;
use php\io\IOException;
use php\io\Stream;
use php\lang\Process;
use php\lib\fs;
use php\lib\str;
use compress\ZipArchive;
use compress\ZipArchiveEntry;

/**
 * Class AndroidPlugin
 *
 * @jppm-task-prefix android
 * @jppm-task init as init
 * @jppm-task compile as compile
 * @jppm-task run as run
 */
class AndroidPlugin {

    // paths
    public const JPHP_COMPILER_PATH = "./.jpfa/compiler.jar";
    public const JPHP_COMPILER_RESOURCE = "res://jpfa/jphp-compiler.jar";
    public const JPHP_BUILD_TEMPLATE_JAVAFX = "res://gradle-build-scripts/javafx.template.groovy";
    public const JPHP_BUILD_TEMPLATE_NATIVE = "res://gradle-build-scripts/native.template.groovy";
    public const GRADLE_WRAPPER_DIR = "./gradle/wrapper";
    public const GRADLE_WRAPPER_JAR_FILE = "./gradle/wrapper/gradle-wrapper.jar";
    public const GRADLE_WRAPPER_JAR_RESOURCE = "res://gradle/wrapper/gradle-wrapper.jar";
    public const GRADLE_WRAPPER_PROP_FILE = "./gradle/wrapper/gradle-wrapper.properties";
    public const GRADLE_WRAPPER_PROP_RESOURCE = "res://gradle/wrapper/gradle-wrapper.properties";
    public const GRADLEW_UNIX_FILE = "./gradlew";
    public const GRADLEW_UNIX_RESOURCE = "res://gradle/gradlew";
    public const GRADLEW_WIN_FILE = "./gradlew.bat";
    public const GRADLEW_WIN_RESOURCE = "res://gradle/gradlew.bat";

    // messages
    public const ANDROID_SDK_READ = "Android SDK Version";
    public const ANDROID_SDK_TOOLS_READ = "Android SDK Tools Version";
    public const ANDROID_UI_READ = "Select android UI [javafx, native]";
    public const PROJECT_NAME_READ = "Android application name";
    public const PROJECT_ID_READ = "Android application ID";

    /**
     * Init android project
     *
     * @param Event $event
     * @throws IOException
     * @throws \php\format\ProcessorException
     */
    public function init(Event $event) {
        $this->check_environment();
        $this->gradle_init();
        $this->prepare_compiler();

        // build config
        $config = [
            "sdk" => $_ENV["JPHP_ANDROID_SDK"] ?: Console::read(AndroidPlugin::ANDROID_SDK_READ, 28),
            "sdk-tools" => $_ENV["JPHP_ANDROID_SDK_TOOLS"] ?:
                Console::read(AndroidPlugin::ANDROID_SDK_TOOLS_READ, "29.0.0"),
            "id" => $_ENV["JPHP_ANDROID_APPLICATION_ID"] ?:
                Console::read(AndroidPlugin::PROJECT_ID_READ, "org.develnext.jphp.android"),
            "ui" => $_ENV["JPHP_ANDROID_UI"] ?:
                Console::read(AndroidPlugin::ANDROID_UI_READ, "javafx")
        ];

        // save config to package.php.yml
        $yaml = fs::parseAs("./" . Package::FILENAME, "yaml");
        $yaml["android"] = $config;
        fs::formatAs("./" . Package::FILENAME, $yaml, "yaml");

        if ($config["ui"] == "javafx") {
            Tasks::run("add", [ "jphp-android-javafx-ui-ext" ], null);
        } elseif ($config["ui"] == "native") {
            Tasks::run("add", [ "jphp-android-native-ui-ext" ], null);
        } else {
            Console::error("Unsupported UI type " . $config["ui"] . ", supported UIs: [javafx, native]");
            exit(102);
        }
    }

    /**
     * @param Event $event
     * @param string $task
     *
     * @throws \php\lang\IllegalArgumentException
     * @throws \php\lang\IllegalStateException
     */
    public function exec_gradle_task(Event $event, string $task) {
        $this->check_environment();
        $this->gradle_init();
        $this->prepare_compiler();
        $this->generate_gradle_build($event);

        Tasks::run("build", [], null);

        $buildFileName = "{$event->package()->getName()}-{$event->package()->getVersion('last')}";
        Console::log('-> unpack jar');
        fs::makeDir('./build/out');

        $zip = new ZipArchive(fs::abs('./build/' . $buildFileName . '.jar'));
        $zip->readAll(function (ZipArchiveEntry $entry, ?Stream $stream) {
            if (!$entry->isDirectory()) {
                fs::makeFile(fs::abs('./build/out/' . $entry->name));
                fs::copy($stream, fs::abs('./build/out/' . $entry->name));
                echo '.';
            } else fs::makeDir(fs::abs('./build/out/' . $entry->name));
        });
        echo ". done\n";

        Console::log('-> starting compiler ...');

        $process = new Process([
            'java', '-jar', AndroidPlugin::JPHP_COMPILER_PATH,
            '--src', './build/out',
            '--dest', './libs/compile.jar'
        ], './');

        $exit = $process->inheritIO()->startAndWait()->getExitValue();

        if ($exit != 0) {
            Console::log("[ERROR] Error compiling jPHP");
            exit($exit);
        } else Console::log(" -> done");

        Console::log('-> starting gradle ...');

        /** @var Process $process */
        $process = (new GradlePlugin($event))->gradleProcess([
            $task
        ])->inheritIO()->startAndWait();

        exit($process->getExitValue());
    }

    /**
     * Compile project
     *
     * @param Event $event
     * @throws \php\lang\IllegalArgumentException
     * @throws \php\lang\IllegalStateException
     */
    public function compile(Event $event) {
        if ($event->package()->getAny('android.ui', "") == "javafx")
            $this->exec_gradle_task($event, "android");
        elseif ($event->package()->getAny('android.ui', "") == "native")
            $this->exec_gradle_task($event, "packageDebug");
        else {
            Console::error("Unable to compile unknown UI type");
            exit(103);
        }
    }

    /**
     * Run project on desktop
     *
     * @param Event $event
     * @throws \php\lang\IllegalArgumentException
     * @throws \php\lang\IllegalStateException
     */
    public function run(Event $event) {
        if ($event->package()->getAny('android.ui', "") == "javafx")
            $this->exec_gradle_task($event, "run");
        elseif ($event->package()->getAny('android.ui', "") == "native")
            // Soon ...
            Console::error("Running native UI type is soon ...");
        else {
            Console::error("Unable to run unknown UI type");
            exit(103);
        }
    }

    protected function prepare_compiler() {
        if (!fs::exists(AndroidPlugin::JPHP_COMPILER_PATH)) {
            Console::log("-> prepare jPHP compiler ...");

            fs::makeDir("./.jpfa/");
            Tasks::createFile(AndroidPlugin::JPHP_COMPILER_PATH,
                fs::get(AndroidPlugin::JPHP_COMPILER_RESOURCE));
        }
    }

    protected function check_environment() {
        if (!$_ENV["ANDROID_HOME"]) {
            Console::error("Environment variable ANDROID_HOME is not set");
            exit(101);
        }
    }

    protected function gradle_init() {
        Console::log('-> install gradle ...');

        Tasks::createDir(AndroidPlugin::GRADLE_WRAPPER_DIR);
        Tasks::createFile(AndroidPlugin::GRADLEW_UNIX_FILE,
            str::replace(fs::get(AndroidPlugin::GRADLEW_UNIX_RESOURCE), "\r\n", "\n"));
        Tasks::createFile(AndroidPlugin::GRADLEW_WIN_FILE,
            fs::get(AndroidPlugin::GRADLEW_WIN_RESOURCE));

        (new File(AndroidPlugin::GRADLEW_UNIX_FILE))
            ->setExecutable(true);

        fs::copy(AndroidPlugin::GRADLE_WRAPPER_JAR_RESOURCE, AndroidPlugin::GRADLE_WRAPPER_JAR_FILE);
        fs::copy(AndroidPlugin::GRADLE_WRAPPER_PROP_RESOURCE, AndroidPlugin::GRADLE_WRAPPER_PROP_FILE);
    }

    protected function generate_gradle_build(Event $event) {
        Console::log('-> prepare build.gradle ...');

        Tasks::createFile("./build.gradle");
        $template = Stream::getContents($event->package()->getAny('android.ui', "javafx") == "javafx" ?
            AndroidPlugin::JPHP_BUILD_TEMPLATE_JAVAFX : AndroidPlugin::JPHP_BUILD_TEMPLATE_NATIVE);
        foreach ($event->package()->getAny('android', []) as $key => $value)
            $template = str::replace($template, "%$key%", $value);

        Stream::putContents("./build.gradle", $template);
    }
}