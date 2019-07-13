package org.venity.jphp.ext.android.fx.classes;

import javafx.scene.control.Button;
import javafx.scene.paint.Color;
import javafx.scene.text.Font;
import org.venity.jphp.ext.android.AndroidExtension;
import org.venity.jphp.ext.android.fx.classes.layout.UXFlowPane;
import org.venity.jphp.ext.android.fx.classes.text.UXFont;
import org.venity.jphp.ext.android.fx.JavaFXExtension;
import org.venity.jphp.ext.android.fx.support.control.PaginationEx;
import php.runtime.annotation.Reflection;
import php.runtime.annotation.Reflection.Property;
import php.runtime.annotation.Reflection.Signature;
import php.runtime.env.Environment;
import php.runtime.reflection.ClassEntity;

@Reflection.Name("UXPagination")
@Reflection.Namespace(AndroidExtension.NS_FX)
public class UXPagination extends UXFlowPane<PaginationEx> {
    interface WrappedInterface {
        @Property int total();
        @Property int pageSize();
        @Property int pageCount();
        @Property int maxPageCount();
        @Property int selectedPage();
        @Property String hintText();
        @Property boolean showTotal();
        @Property Color textColor();
        @Property boolean showPrevNext();

        @Property Button previousButton();
        @Property Button nextButton();
    }

    public UXPagination(Environment env, PaginationEx wrappedObject) {
        super(env, wrappedObject);
    }

    public UXPagination(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Override
    public PaginationEx getWrappedObject() {
        return (PaginationEx) super.getWrappedObject();
    }

    @Override
    @Signature
    public void __construct() {
        __wrappedObject = new PaginationEx();
    }


    @Reflection.Getter
    public UXFont getFont(Environment env) {
        return new UXFont(env, getWrappedObject().getFont(), font -> getWrappedObject().setFont(font));
    }

    @Reflection.Setter
    public void setFont(Font font) {
        getWrappedObject().setFont(font);
    }
}
