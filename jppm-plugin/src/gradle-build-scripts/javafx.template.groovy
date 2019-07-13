buildscript {
    repositories {
        maven {
            name 'gulon'
            url  "http://nexus.gluonhq.com/nexus/content/repositories/releases/"
        }

        mavenLocal()
        jcenter()
        google()
    }

    dependencies {
        classpath 'org.javafxports:jfxmobile-plugin:1.3.10'
    }
}

apply plugin: 'org.javafxports.jfxmobile'

repositories {
    maven {
        name 'gulon'
        url  "http://nexus.gluonhq.com/nexus/content/repositories/releases/"
    }

    mavenLocal()
    jcenter()
    google()
}

dependencies {
    androidRuntime 'com.gluonhq:charm-down-core-android:3.5.0'
    compile fileTree(dir: 'libs', include: ['*.jar'])
}

jfxmobile {
    javafxportsVersion = '8.60.9'
    downConfig {
        version = '3.8.0'
        plugins 'display', 'lifecycle', 'statusbar', 'storage'
    }
    android {
        compileSdkVersion  = %sdk%
        buildToolsVersion  = "%sdk-tools%"
        applicationPackage = "%id%"
    }
}

mainClassName = "org.venity.jphp.ext.android.UXAndroidApplication"