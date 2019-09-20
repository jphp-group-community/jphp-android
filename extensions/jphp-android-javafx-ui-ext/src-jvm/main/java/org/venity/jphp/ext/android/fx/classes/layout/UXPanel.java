package org.venity.jphp.ext.android.fx.classes.layout;

import javafx.geometry.Pos;
import javafx.scene.paint.Color;
import javafx.scene.text.Font;
import org.venity.jphp.ext.android.fx.JavaFXExtension;
import org.venity.jphp.ext.android.fx.support.control.Panel;
import php.runtime.annotation.Reflection;
import php.runtime.annotation.Reflection.Nullable;
import php.runtime.annotation.Reflection.Property;
import php.runtime.env.Environment;
import php.runtime.reflection.ClassEntity;

@Reflection.Name("UXPanel")
@Reflection.Namespace("php\\gui\\layout")
public class UXPanel<T extends Panel> extends UXAnchorPane<Panel> {
    interface WrappedInterface {
        @Property String title();
        @Property Color titleColor();
        @Property Font titleFont();
        @Property double titleOffset();
        @Property Pos titlePosition();

        @Property @Nullable Color borderColor();
        @Property double borderWidth();
        @Property double borderRadius();
        @Property String borderStyle();
    }

    public UXPanel(Environment env, T wrappedObject) {
        super(env, wrappedObject);
    }

    public UXPanel(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Override
    public void __construct() {
        __wrappedObject = new Panel();
    }

    @Override
    @SuppressWarnings("unchecked")
    public T getWrappedObject() {
        return (T) super.getWrappedObject();
    }

    @Override
    @Reflection.Setter
    public void setBackgroundColor(@Nullable Color color) {
        getWrappedObject().setBackgroundColor(color);
    }

    @Override
    @Reflection.Getter
    public Color getBackgroundColor() {
        return getWrappedObject().getBackgroundColor();
    }
}