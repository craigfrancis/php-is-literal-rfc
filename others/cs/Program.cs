using System.Diagnostics.CodeAnalysis;

internal class Program
{

    private static void RunSQL([ConstantExpected] string SQL, params string[] parameters)
    {
        Console.WriteLine(SQL);
    }

    private static void Main(string[] args)
    {

        string id;
        if (args.Length > 0) {
            id = args[0];
        } else {
            id = "1";
        }

        RunSQL("SELECT * FROM table WHERE id = ?", [id]);

        RunSQL("SELECT * FROM table WHERE id = " + id);

    }
}
